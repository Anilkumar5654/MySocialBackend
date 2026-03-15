<?php

namespace App\Controllers\Api\Ads;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\AdsModel;
use Throwable;

class CampaignController extends BaseController {
    use ResponseTrait;
    protected $adsModel;
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        $this->adsModel = new AdsModel();
        helper(['media', 'url', 'text', 'filesystem', 'date', 'currency']);
    }

    /**
     * ⚙️ GET AD SETTINGS
     */
    public function get_settings() {
        try {
            $userId = $this->request->getHeaderLine('User-ID');
            $user = $this->db->table('users')->select('preferred_currency')->where('id', $userId)->get()->getRow();
            $currency = $user->preferred_currency ?? 'INR';

            $rows = $this->db->table('ad_settings')->get()->getResultArray();
            $settings = [];
            foreach ($rows as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            $defaults = ['min_cpc_bid' => '0.50', 'min_cpm_bid' => '1.00', 'min_cpv_bid' => '0.10', 'usd_to_inr'  => '83.00'];
            $finalData = array_merge($defaults, $settings);

            foreach ($finalData as $key => $val) {
                if (strpos($key, '_bid') !== false) {
                    $finalData[$key . '_display'] = format_currency((float)$val, $currency);
                }
            }

            return $this->respond(['success' => true, 'data' => $finalData, 'currency' => $currency]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 🚀 1. CREATE CAMPAIGN
     */
    public function create() {
        try {
            $user_id = $this->request->getHeaderLine('User-ID');
            if (!$user_id) return $this->failUnauthorized();

            $allInputs = [];
            $contentType = $this->request->getHeaderLine('Content-Type');
            if (strpos($contentType, 'application/json') !== false) {
                $allInputs = $this->request->getJSON(true) ?? [];
            } else {
                $allInputs = $this->request->getPost();
            }

            $dailyLimit = (float)($allInputs['daily_limit'] ?? 0);
            $duration   = (int)($allInputs['duration'] ?? 0);
            $totalBudgetRequired = $dailyLimit * $duration;

            if ($totalBudgetRequired <= 0) return $this->fail("Invalid budget or duration.");

            $spendingWallet = $this->db->table('spending_wallets')->where('user_id', $user_id)->get()->getRow();
            if (!$spendingWallet || (float)$spendingWallet->balance < $totalBudgetRequired) {
                return $this->fail("Insufficient balance. Required: ₹" . number_format($totalBudgetRequired, 2));
            }

            $data = [
                'advertiser_id'   => $user_id,
                'title'           => esc($allInputs['title'] ?? 'Untitled Ad'),
                'daily_limit'     => $dailyLimit, 
                'budget'          => $totalBudgetRequired, 
                'bid_type'        => $allInputs['bid_type'] ?? 'cpc',
                'start_date'      => date('Y-m-d H:i:s'),
                'end_date'        => date('Y-m-d H:i:s', strtotime("+$duration days")),
                'status'          => 'pending_approval', 
                'created_at'      => date('Y-m-d H:i:s'),
                'ad_type'         => $allInputs['ad_type'] ?? 'custom_ad',
                'spent'           => 0.00,
                'impressions'     => 0, 'reach' => 0, 'views' => 0, 'clicks' => 0
            ];

            if (!empty($allInputs['post_id'])) {
                $postId = $allInputs['post_id'];
                $placementReq = $allInputs['placement'] ?? 'feed';
                $sourceType = (strpos($placementReq, 'reel') !== false) ? 'reel' : 'video';
                $sourceTable = ($sourceType === 'reel') ? 'reels' : 'videos';
                $content = $this->db->table($sourceTable)->where(['id' => $postId, 'user_id' => $user_id])->get()->getRow();
                if ($content) {
                    $data = array_merge($data, [
                        'source_post_id' => $postId,
                        'source_type'    => $sourceType,
                        'media_type'     => 'video',
                        'media_url'      => $content->video_url ?? $content->media_url,
                        'thumbnail_url'  => $content->thumbnail_url,
                        'target_url'     => "mysocial://post/" . $postId,
                        'placement'      => $sourceType,
                        'is_external'    => 0,             
                        'cta_label'      => 'Watch Now'    
                    ]);
                }
            } else {
                $data = array_merge($data, [
                    'target_url'  => $allInputs['target_url'] ?? '',
                    'placement'   => $allInputs['placement'] ?? 'feed',
                    'is_external' => 1,
                    'cta_label'   => $allInputs['cta_label'] ?? 'Learn More'
                ]);
                $file = $this->request->getFile('media_file');
                if ($file && $file->isValid()) {
                    $isVideo = strpos($file->getMimeType(), 'video') !== false;
                    $data['media_type'] = $isVideo ? 'video' : 'image';
                    $data['media_url']  = upload_media_master($file, $isVideo ? 'ads_video' : 'ads_image');
                }
            }

            $this->db->transStart();
            $this->db->table('spending_wallets')->where('user_id', $user_id)->decrement('balance', $totalBudgetRequired);
            $this->adsModel->insert($data);
            $this->db->transComplete();

            return $this->respondCreated(['status' => true, 'ad_id' => $this->adsModel->getInsertID()]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 📊 2. MY ADS LIST - FIXED HTTP 500
     */
    public function my_ads() {
        try {
            $user_id = $this->request->getHeaderLine('User-ID');
            if (!$user_id) return $this->failUnauthorized();

            $user = $this->db->table('users')->select('preferred_currency')->where('id', $user_id)->get()->getRow();
            $currency = $user->preferred_currency ?? 'INR';
            
            // 🔥 MASTER FIX: asArray() ensures array_map works perfectly
            $ads = $this->adsModel->asArray()
                        ->where('advertiser_id', $user_id)
                        ->where('status !=', 'deleted')
                        ->orderBy('created_at', 'DESC')
                        ->findAll();

            $formatted = array_map(function($ad) use ($currency) {
                return [
                    'ad_id'          => (int)$ad['id'],
                    'title'          => $ad['title'],
                    'status'         => $ad['status'],
                    'placement'      => $ad['placement'],
                    // ✅ IS_BOOSTED LOGIC INCLUDED
                    'is_boosted'     => !empty($ad['source_post_id']), 
                    'budget_display' => format_currency((float)$ad['budget'], $currency),
                    'metrics' => [
                        'reach'   => (int)($ad['reach'] ?? 0),
                        'views'   => (int)($ad['views'] ?? 0),
                        'clicks'  => (int)($ad['clicks'] ?? 0),
                    ],
                    'thumbnail'      => get_media_url($ad['thumbnail_url'] ?: $ad['media_url'], 'ads_image'),
                    'created_at'     => date('d M, Y', strtotime($ad['created_at']))
                ];
            }, $ads);

            return $this->respond(['status' => true, 'data' => $formatted]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 📈 3. GET ANALYTICS - FIXED HTTP 500
     */
    public function get_analytics() {
        try {
            $user_id = $this->request->getHeaderLine('User-ID');
            $ad_id = $this->request->getVar('ad_id');
            if (!$ad_id) return $this->fail("Ad ID missing.");

            // 🔥 MASTER FIX: asArray() here too
            $ad = $this->adsModel->asArray()->where(['id' => $ad_id, 'advertiser_id' => $user_id])->first();
            if (!$ad) return $this->failNotFound("Not found.");

            $user = $this->db->table('users')->select('preferred_currency')->where('id', $user_id)->get()->getRow();
            $currency = $user->preferred_currency ?? 'INR';

            $reach  = (int)($ad['reach'] ?? 0);
            $views  = (int)($ad['views'] ?? 0);
            $clicks = (int)($ad['clicks'] ?? 0);
            $spent  = (float)($ad['spent'] ?? 0);
            $budget = (float)($ad['budget'] ?? 0);

            return $this->respond(['status' => true, 'data' => [
                'summary' => [
                    'reach'         => $reach,
                    'views'         => $views,
                    'clicks'        => $clicks,
                    'impressions'   => (int)($ad['impressions'] ?? 0),
                    'spent'         => format_currency($spent, $currency),
                    'total_budget'  => format_currency($budget, $currency),
                    'spent_numeric' => $spent,
                    'total_budget_numeric' => $budget,
                    'ctr'           => ($reach > 0) ? round(($views / $reach) * 100, 2) : 0,
                    'cpc'           => format_currency(($clicks > 0 ? $spent / $clicks : 0), $currency),
                ],
                'daily_stats' => $this->getAdvancedDailyStats((int)$ad_id, 7),
                'details'     => [
                    'title'         => $ad['title'],
                    'status'        => strtoupper($ad['status']),
                    'placement'     => strtoupper($ad['placement']),
                    'bid_type'      => $ad['bid_type'],
                    'thumbnail_url' => get_media_url($ad['thumbnail_url'] ?: $ad['media_url'], 'ads_image')
                ]
            ]]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function toggle_status() {
        $user_id = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        $ad_id = $json['ad_id'] ?? null;
        $action = $json['action'] ?? null; 
        $statusMap = ['pause' => 'paused', 'resume' => 'active', 'delete' => 'deleted'];
        if (!$ad_id || !isset($statusMap[$action])) return $this->fail("Invalid request.");
        $this->adsModel->update($ad_id, ['status' => $statusMap[$action]]);
        return $this->respond(['status' => true, 'new_status' => $statusMap[$action]]);
    }

    private function getAdvancedDailyStats(int $ad_id, int $days) {
        $list = [];
        $startDate = date('Y-m-d', strtotime("-".($days-1)." days"));
        $stats = $this->db->table('ad_impressions')->select("DATE(created_at) as date, COUNT(*) as count")->where(['ad_id' => $ad_id, 'created_at >=' => $startDate . ' 00:00:00'])->groupBy('date')->get()->getResultArray();
        $map = array_column($stats, 'count', 'date');
        $maxVal = 0;
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $val = (int)($map[$date] ?? 0);
            if($val > $maxVal) $maxVal = $val;
            $list[] = ['label' => date('d M', strtotime($date)), 'value' => $val]; 
        }
        return ['stats' => $list, 'max_value' => ($maxVal > 0 ? $maxVal : 10)];
    }
}
