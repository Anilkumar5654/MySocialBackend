<?php

namespace App\Controllers\Api\Ads;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\AdSecurityHelper;
use Throwable;

class TrackingController extends BaseController {
    use ResponseTrait;
    protected $db;
    protected $ad_config = [];
    protected $security;

    public function __construct() {
        $this->db = \Config\Database::connect();
        $this->security = new AdSecurityHelper();
        $this->load_settings();
        helper(['date']);
    }

    private function load_settings() {
        $tables = ['ad_settings', 'ad_network_settings'];
        foreach ($tables as $table) {
            $rows = $this->db->table($table)->get()->getResultArray();
            foreach ($rows as $row) {
                $this->ad_config[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    public function track_view() { return $this->process_tracking('views'); }
    public function track_click() { return $this->process_tracking('clicks'); }
    public function track_impression() { return $this->process_tracking('impressions'); }

    private function process_tracking($type) {
        try {
            if (($this->ad_config['global_ad_status'] ?? 'active') !== 'active') return $this->respond(['status' => false, 'debug' => 'Offline']);

            $json = $this->request->getJSON(true);
            $ad_id    = (int)($json['ad_id'] ?? 0);
            $video_id = (int)($json['video_id'] ?? 0); 
            $is_reel  = filter_var($json['is_reel'] ?? false, FILTER_VALIDATE_BOOLEAN);
            
            if (!$ad_id) return $this->fail("Invalid Ad ID");

            // 🔍 1. Ad Details
            $ad = $this->db->table('ads')
                ->select('ads.*, users.avatar as advertiser_avatar, users.username as advertiser_name')
                ->join('users', 'users.id = ads.advertiser_id', 'left')
                ->where('ads.id', $ad_id)
                ->get()->getRow();

            if (!$ad || $ad->status !== 'active') return $this->respond(['status' => false]);

            // 🔥 2. Cost Calculation
            $cost = 0;
            $bid_type = $ad->bid_type;

            if ($type === 'impressions' && $bid_type === 'cpm') {
                $cost = (float)($this->ad_config['min_cpm_bid'] ?? 1) / 1000;
            } 
            elseif ($type === 'views' && $bid_type === 'cpv') {
                $cost = (float)($this->ad_config['min_cpv_bid'] ?? 1);
            } 
            elseif ($type === 'clicks' && $bid_type === 'cpc') {
                $cost = (float)($this->ad_config['min_cpc_bid'] ?? 0.50);
            }

            // 🛡️ 3. Revenue Sharing
            $sharePercent = (int)($this->ad_config['revenue_share_ads'] ?? 50);
            $logic = $this->security->getRevenueLogic($video_id, $is_reel, $cost, $sharePercent, $ad->advertiser_id);
            
            $viewer_id = $this->request->getHeaderLine('User-ID');
            $device_id = $this->request->getHeaderLine('Device-ID') ?: ($json['device_id'] ?? 'unknown');

            // ✅ SKIP LOGIC DATA (ad_logs update)
            if ($type === 'views' && $viewer_id) {
                $today = date('Y-m-d');
                $existingLog = $this->db->table('ad_logs')
                    ->where(['user_id' => $viewer_id, 'ad_id' => $ad_id])
                    ->where('DATE(created_at)', $today)
                    ->get()->getRow();

                if ($existingLog) {
                    $this->db->table('ad_logs')->where('id', $existingLog->id)->set('view_count', 'view_count + 1', false)->update();
                } else {
                    $this->db->table('ad_logs')->insert([
                        'user_id' => $viewer_id, 'ad_id' => $ad_id, 'placement' => ($logic['actual_is_reel'] ?? $is_reel) ? 'reel' : 'feed',
                        'action' => 'view', 'view_count' => 1, 'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // 🛡️ 4. ANTI-SPAM PROTECTION (Lightweight 30 Sec Check)
            $targetTable = ($type === 'views') ? 'ad_views' : (($type === 'clicks') ? 'ad_clicks' : 'ad_impressions');
            
            $spamCheck = $this->db->table($targetTable)
                ->where('ad_id', $ad_id)
                ->where('user_id', $viewer_id)
                ->where('created_at >', date('Y-m-d H:i:s', strtotime('-30 seconds')))
                ->countAllResults();
            
            if ($spamCheck > 0) return $this->respond(['status' => true, 'debug' => 'Spam-Protected']);

            if ($this->security->isFraud($viewer_id, $logic['creator_id'])) return $this->respond(['status' => true, 'debug' => 'Self-view ignored']);

            // 🔥 5. Polymorphic Setup
            $final_is_reel = $logic['actual_is_reel'] ?? $is_reel;
            $content_id = $video_id > 0 ? $video_id : null;
            $content_type = $content_id ? ($final_is_reel ? 'reel' : 'video') : null;

            // ⚡ 6. Database Execution (FASTEST - No reach calculation here)
            $this->db->transStart(); 
            
            // Ad statistics update
            $this->db->table('ads')->where('id', $ad_id)
                ->set($type, "$type + 1", false)
                ->set('spent', "spent + $cost", false)
                ->update();

            // Insert tracking record (Fire & Forget)
            $logData = [
                'ad_id' => $ad_id, 'user_id' => $viewer_id ?: null, 'creator_id' => $logic['creator_id'] ?: null,
                'device_id' => $device_id, 'ip_address' => $this->request->getIPAddress(), 
                'content_type' => $content_type, 'content_id' => $content_id,     
                'cost' => $cost, 'creator_revenue' => $logic['revenue'], 
                'is_settled' => 0, 'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($type === 'impressions') unset($logData['creator_revenue'], $logData['is_settled'], $logData['creator_id']);

            $this->db->table($targetTable)->insert($logData);

            if ($type === 'views' && $content_id && $content_type) {
                $this->db->table($content_type . 's')->where('id', $content_id)->increment('views_count');
            }

            // 🛑 AUTO-STOP (Budget Exceeded)
            if (($ad->spent + $cost) >= $ad->budget) {
                $this->db->table('ads')->where('id', $ad_id)->update(['status' => 'inactive']);
            }

            $this->db->transComplete(); 
            return $this->respond(['status' => true, 'revenue' => $logic['revenue'], 'debug' => $logic['debug']]);

        } catch (Throwable $e) { return $this->respond(['status' => false, 'error' => $e->getMessage()]); }
    }
}
