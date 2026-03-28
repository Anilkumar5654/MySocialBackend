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

            $title = esc($allInputs['title'] ?? 'Untitled Ad');
            $duplicateCheck = $this->adsModel->where('advertiser_id', $user_id)
                                             ->where('title', $title)
                                             ->where('created_at >', date('Y-m-d H:i:s', strtotime('-30 seconds')))
                                             ->first();

            if ($duplicateCheck) return $this->failResourceExists("Duplicate campaign detected. Please wait a moment.");

            $totalBudgetRequired = (float)($allInputs['budget'] ?? 0);
            if ($totalBudgetRequired <= 0) return $this->fail("Invalid budget.");

            $spendingWallet = $this->db->table('spending_wallets')->where('user_id', $user_id)->get()->getRow();
            if (!$spendingWallet || (float)$spendingWallet->balance < $totalBudgetRequired) {
                return $this->fail("Insufficient balance. Required: ₹" . number_format($totalBudgetRequired, 2));
            }

            $targeting = [
                'country'   => $allInputs['target_country'] ?? 'Global',
                'age_group' => $allInputs['age_group'] ?? '18-24',
                'gender'    => 'all'
            ];

            $data = [
                'advertiser_id'   => $user_id,
                'title'           => $title,
                'budget'          => $totalBudgetRequired, 
                'daily_limit'     => (float)($allInputs['daily_limit'] ?? 0),
                'bid_type'        => $allInputs['bid_type'] ?? 'cpc',
                'targeting_data'  => json_encode($targeting),
                'start_date'      => date('Y-m-d H:i:s'),
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
                        'ad_type'        => 'boosted_content'
                    ]);
                }
            } else {
                $data = array_merge($data, [
                    'target_url'  => $allInputs['target_url'] ?? '',
                    'placement'   => $allInputs['placement'] ?? 'feed',
                    'media_type'  => 'video', 
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
     * 📊 2. MY ADS LIST (🔥 FIX: Live Reach Calculation Added)
     */
    public function my_ads() {
        try {
            $user_id = $this->request->getHeaderLine('User-ID');
            if (!$user_id) return $this->failUnauthorized();

            $user = $this->db->table('users')->select('preferred_currency')->where('id', $user_id)->get()->getRow();
            $currency = $user->preferred_currency ?? 'INR';
            
            $ads = $this->adsModel->asArray()
                        ->where('advertiser_id', $user_id)
                        ->where('status !=', 'deleted')
                        ->orderBy('created_at', 'DESC')
                        ->findAll();

            // Fetch live stats for all these ads in one query
            $ad_ids = array_column($ads, 'id');
            $live_stats = [];
            if (!empty($ad_ids)) {
                $statsResult = $this->db->table('ad_impressions')
                    ->select('ad_id, COUNT(*) as imp_count, COUNT(DISTINCT user_id) as reach_count')
                    ->whereIn('ad_id', $ad_ids)
                    ->groupBy('ad_id')
                    ->get()->getResultArray();
                foreach($statsResult as $row) {
                    $live_stats[$row['ad_id']] = $row;
                }
            }

            $formatted = array_map(function($ad) use ($currency, $live_stats) {
                // Live stats fetch, fallback to 0
                $live = $live_stats[$ad['id']] ?? ['imp_count' => 0, 'reach_count' => 0];

                return [
                    'ad_id'          => (int)$ad['id'],
                    'title'          => $ad['title'],
                    'status'         => $ad['status'],
                    'placement'      => $ad['placement'],
                    'is_boosted'     => !empty($ad['source_post_id']), 
                    'budget_display' => format_currency((float)$ad['budget'], $currency),
                    'metrics' => [
                        'reach'   => (int)$live['reach_count'], // 🔥 LIVE REACH
                        'views'   => (int)$live['imp_count'],   // 🔥 LIVE VIEWS/IMPRESSIONS
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
     * 📈 3. GET ANALYTICS (🔥 FIX: Live Calculation & Correct Math)
     */
    public function get_analytics() {
        try {
            $user_id = $this->request->getHeaderLine('User-ID');
            $ad_id = $this->request->getVar('ad_id');
            if (!$ad_id) return $this->fail("Ad ID missing.");

            $ad = $this->adsModel->asArray()->where(['id' => $ad_id, 'advertiser_id' => $user_id])->first();
            if (!$ad) return $this->failNotFound("Not found.");

            $userPref = $this->db->table('users')->select('preferred_currency')->where('id', $user_id)->get()->getRow();
            $currency = $userPref->preferred_currency ?? 'INR';

            // 🔥 Fetch LIVE Impressions and Reach from ad_impressions table
            $liveStats = $this->db->table('ad_impressions')
                ->select('COUNT(*) as total_impressions, COUNT(DISTINCT user_id) as total_reach')
                ->where('ad_id', $ad_id)
                ->get()->getRow();

            $reach       = (int)$liveStats->total_reach;
            $impressions = (int)$liveStats->total_impressions;
            $views       = $impressions; 
            
            $clicks = (int)($ad['clicks'] ?? 0);
            $spent  = (float)($ad['spent'] ?? 0);
            $budget = (float)($ad['budget'] ?? 0);

            // 🔥 GEOGRAPHY Logic (Updated: Uses country column directly)
            $geoStats = $this->db->table('ad_impressions ai')
                ->select('u.country, COUNT(*) as count')
                ->join('users u', 'u.id = ai.user_id', 'left')
                ->where('ai.ad_id', $ad_id)
                ->groupBy('u.country')
                ->get()->getResultArray();
            
            $countryCounts = [];
            foreach ($geoStats as $gs) {
                $country = $gs['country'] ?: 'Global';
                if (!isset($countryCounts[$country])) $countryCounts[$country] = 0;
                $countryCounts[$country] += $gs['count'];
            }

            arsort($countryCounts);
            $countryCounts = array_slice($countryCounts, 0, 5);
            
            $geoData = [];
            $totalGeo = array_sum($countryCounts) ?: 1;
            foreach ($countryCounts as $country => $count) {
                $geoData[] = [
                    'country' => $country, 
                    'reach' => round(($count / $totalGeo) * 100, 1)
                ];
            }

            // GENDER Logic
            $genderStats = $this->db->table('ad_impressions ai')
                ->select('u.gender, COUNT(*) as count')
                ->join('users u', 'u.id = ai.user_id', 'left')
                ->where('ai.ad_id', $ad_id)->groupBy('u.gender')->get()->getResultArray();
            
            $genderData = [];
            $sumGender = array_sum(array_column($genderStats, 'count')) ?: 1;
            foreach ($genderStats as $gs) {
                $gen = strtolower($gs['gender'] ?: 'other');
                $genderData[] = [
                    'label' => ucfirst($gen),
                    'value' => round(($gs['count'] / $sumGender) * 100, 1),
                    'color' => ($gen == 'male' ? '#3b82f6' : ($gen == 'female' ? '#ec4899' : '#94a3b8'))
                ];
            }

            // AGE Logic
            $ageStats = $this->db->table('ad_impressions ai')
                ->select("CASE 
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) < 18 THEN 'U-18'
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    ELSE '45+' END as age_group, COUNT(*) as count")
                ->join('users u', 'u.id = ai.user_id', 'left')
                ->where('ai.ad_id', $ad_id)->groupBy('age_group')->get()->getResultArray();
            
            $ageData = [];
            $sumAge = array_sum(array_column($ageStats, 'count')) ?: 1;
            foreach ($ageStats as $as) {
                $label = $as['age_group'] ?: 'Unknown';
                $ageData[] = ['label' => $label, 'value' => round(($as['count'] / $sumAge) * 100, 1)];
            }

            return $this->respond(['status' => true, 'data' => [
                'summary' => [
                    'reach'         => $reach,
                    'views'         => $views,
                    'clicks'        => $clicks,
                    'impressions'   => $impressions,
                    'spent'         => format_currency($spent, $currency),
                    'spent_numeric' => $spent,
                    'total_budget'  => format_currency($budget, $currency),
                    'total_budget_numeric' => $budget,
                    'ctr'           => ($impressions > 0) ? round(($clicks / $impressions) * 100, 2) : 0,
                    'cpc'           => format_currency(($clicks > 0 ? $spent / $clicks : 0), $currency),
                    'cpm'           => format_currency(($impressions > 0 ? ($spent / $impressions) * 1000 : 0), $currency),
                    'interest_rate' => ($reach > 0) ? round(($views / $reach) * 100, 1) : 0,
                ],
                'details' => [
                    'title'         => $ad['title'],
                    'status'        => strtoupper($ad['status']),
                    'objective'     => strtoupper($ad['ad_type']),
                    'thumbnail_url' => get_media_url($ad['thumbnail_url'] ?: $ad['media_url'], 'ads_image')
                ],
                'demographics' => ['gender' => $genderData, 'age' => $ageData],
                'geo' => $geoData,
                'devices' => [['label' => 'Android', 'value' => 88], ['label' => 'iOS', 'value' => 10], ['label' => 'Web', 'value' => 2]],
                'daily_stats' => $this->getAdvancedDailyStats((int)$ad_id, 7)
            ]]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * ⏸️ TOGGLE STATUS
     */
    public function toggle_status() {
        try {
            $user_id = $this->request->getHeaderLine('User-ID');
            $json = $this->request->getJSON(true) ?? $this->request->getPost();
            $ad_id = $json['ad_id'] ?? null;
            $action = $json['action'] ?? null; 
            $statusMap = ['pause' => 'paused', 'resume' => 'active', 'delete' => 'deleted'];
            if (!$ad_id || !isset($statusMap[$action])) return $this->fail("Invalid request.");
            $this->adsModel->update($ad_id, ['status' => $statusMap[$action]]);
            return $this->respond(['status' => true, 'new_status' => $statusMap[$action]]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 📈 DAILY STATS CHART
     */
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
