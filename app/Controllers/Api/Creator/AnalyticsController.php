<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class AnalyticsController extends BaseController
{
    use ResponseTrait;

    public function detail($type)
    {
        try {
            $db = \Config\Database::connect();
            
            // 🔥 TIMEZONE
            date_default_timezone_set('Asia/Kolkata');

            // 🔥 FIX: Request header se User-ID
            $userId = $this->request->getHeaderLine('User-ID'); 

            if (empty($userId)) {
                return $this->failUnauthorized('User ID missing in header');
            }

            $period = $this->request->getGet('period') ?? '28D';
            $startDate = $this->getStartDate($period);
            
            $daysCount = (int)filter_var($period, FILTER_SANITIZE_NUMBER_INT) ?: 28;

            // ==========================================
            // 📊 2. CHANNEL KPI SUMMARY
            // ==========================================
            $user = $db->table('users')->select('preferred_currency')->where('id', $userId)->get()->getRow();
            $prefCurrency = $user->preferred_currency ?? 'INR'; 
            $channel = $db->table('channels')->select('is_monetization_enabled')->where('user_id', $userId)->get()->getRow();
            
            $totalFollowers = $db->table('follows')->where('following_id', $userId)->countAllResults();
            $newFollowersGained = $db->table('follows')
                ->where('following_id', $userId)
                ->where('created_at >=', $startDate)
                ->countAllResults();

            $totalViews = $db->table('views')
                ->where('creator_id', $userId)
                ->where('created_at >=', $startDate)
                ->countAllResults();

            $totalImpressions = $db->table('impressions')
                ->where('creator_id', $userId)
                ->where('created_at >=', $startDate)
                ->countAllResults();

            $watchTimeRow = $db->table('views')
                ->selectSum('watch_duration', 'total_duration')
                ->where('creator_id', $userId) 
                ->where('created_at >=', $startDate)
                ->get()->getRow();
            $watchDuration = ($watchTimeRow && isset($watchTimeRow->total_duration)) ? $watchTimeRow->total_duration : 0;
            $watchTimeHours = $watchDuration / 3600;
            $formattedWatchTime = round($watchTimeHours, 2);

            // ==========================================
            // 🌟 REAL ENGAGEMENT CALCULATION
            // ==========================================
            $likeVideo = $db->table('likes l')->join('videos v', 'v.id = l.likeable_id')->where('v.user_id', $userId)->where('l.likeable_type', 'video')->where('l.created_at >=', $startDate)->countAllResults();
            $likeReel = $db->table('likes l')->join('reels r', 'r.id = l.likeable_id')->where('r.user_id', $userId)->where('l.likeable_type', 'reel')->where('l.created_at >=', $startDate)->countAllResults();
            $commVideo = $db->table('comments c')->join('videos v', 'v.id = c.commentable_id')->where('v.user_id', $userId)->where('c.commentable_type', 'video')->where('c.created_at >=', $startDate)->countAllResults();
            $commReel = $db->table('comments c')->join('reels r', 'r.id = c.commentable_id')->where('r.user_id', $userId)->where('c.commentable_type', 'reel')->where('c.created_at >=', $startDate)->countAllResults();
            
            $totalInteractions = $likeVideo + $likeReel + $commVideo + $commReel;
            $engagementRate = $totalViews > 0 ? round(($totalInteractions / $totalViews) * 100, 1) : 0;

            // ==========================================
            // 🔥 REAL GRAPH GENERATION
            // ==========================================
            $graphDataQuery = [];
            
            if ($type === 'impressions') {
                $graphDataQuery = $db->table('impressions')
                    ->select('DATE(created_at) as date, COUNT(id) as count')
                    ->where('creator_id', $userId)->where('created_at >=', $startDate)
                    ->groupBy('DATE(created_at)')->get()->getResultArray();
            } elseif ($type === 'watchTime' || $type === 'watchtime') {
                $graphDataQuery = $db->table('views')
                    ->select('DATE(created_at) as date, SUM(watch_duration) as count')
                    ->where('creator_id', $userId)->where('created_at >=', $startDate)
                    ->groupBy('DATE(created_at)')->get()->getResultArray();
            } elseif ($type === 'subs' || $type === 'followers') {
                $graphDataQuery = $db->table('follows')
                    ->select('DATE(created_at) as date, COUNT(id) as count')
                    ->where('following_id', $userId)->where('created_at >=', $startDate)
                    ->groupBy('DATE(created_at)')->get()->getResultArray();
            } elseif ($type === 'revenue' || $type === 'reward') {
                 $graphDataQuery = $db->table('creator_earnings')
                    ->select('DATE(created_at) as date, SUM(amount) as count')
                    ->where('user_id', $userId)->where('created_at >=', $startDate)
                    ->groupBy('DATE(created_at)')->get()->getResultArray();
            } else {
                $graphDataQuery = $db->table('views')
                    ->select('DATE(created_at) as date, COUNT(id) as count')
                    ->where('creator_id', $userId)->where('created_at >=', $startDate)
                    ->groupBy('DATE(created_at)')->get()->getResultArray();
            }

            $dailyData = [];
            foreach($graphDataQuery as $row) { $dailyData[$row['date']] = (float)$row['count']; }

            $chartPoints = [];
            $maxVal = count($dailyData) > 0 ? max(array_values($dailyData)) : 10;
            if ($maxVal == 0) $maxVal = 10; 
            
            for ($i = $daysCount; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $val = $dailyData[$date] ?? 0;
                $x = round(($daysCount - $i) * (150 / $daysCount));
                $y = round(100 - (($val / $maxVal) * 90)); 
                $chartPoints[] = "$x,$y";
            }
            $realChartData = implode(" ", $chartPoints);

            // ==========================================
            // 🎬 3. TOP PERFORMING CONTENT
            // ==========================================
            $formattedRecent = [];
            $contentLimit = (empty($type) || $type === 'all') ? 5 : 15;

            if ($type === 'revenue' || $type === 'reward') {
                $topEarners = $db->table('creator_earnings')
                    ->select('content_id as id, content_type as type, SUM(amount) as period_revenue')
                    ->where('user_id', $userId)->where('created_at >=', $startDate)
                    ->groupBy('content_id, content_type')->orderBy('period_revenue', 'DESC')
                    ->limit($contentLimit)->get()->getResultArray();

                foreach ($topEarners as $earner) {
                    $cType = strtolower($earner['type']);
                    $table = ($cType === 'reel') ? 'reels' : 'videos';
                    $titleColumn = ($cType === 'reel') ? 'caption as title' : 'title';
                    $content = $db->table($table)->select($titleColumn . ', thumbnail_url, status')->where('id', $earner['id'])->get()->getRow();
                    
                    if (!$content) continue;

                    $viewStats = $db->table('views')->select('COUNT(id) as period_views, SUM(watch_duration) as period_watchtime')
                        ->where('viewable_id', $earner['id'])->where('viewable_type', $cType)->where('created_at >=', $startDate)->get()->getRow();

                    $itemImpressions = $db->table('impressions')
                        ->where('impressionable_id', $earner['id'])->where('impressionable_type', $cType)
                        ->where('created_at >=', $startDate)->countAllResults();

                    $rawTitle = $content->title ?? '';
                    $pViews = (int)($viewStats->period_views ?? 0);
                    
                    $formattedRecent[] = [
                        'id'          => (string)$earner['id'],
                        'type'        => $cType,
                        'title'       => !empty($rawTitle) ? (strlen($rawTitle) > 25 ? substr($rawTitle, 0, 25) . '...' : $rawTitle) : "Untitled " . ucfirst($cType),
                        'views'       => $pViews,
                        'thumbnail'   => !empty($content->thumbnail_url) ? (function_exists('get_media_url') ? get_media_url($content->thumbnail_url) : $content->thumbnail_url) : 'default.jpg',
                        'status'      => strtoupper($content->status ?? 'PUBLISHED'),
                        'watchTime'   => round(($viewStats->period_watchtime ?? 0) / 3600, 2),
                        'followers'   => "No data", 
                        'impressions' => $itemImpressions,
                        'ctr'         => $itemImpressions > 0 ? round(($pViews / $itemImpressions) * 100, 1) : 0,
                        'revenue'     => get_currency_object($earner['period_revenue'], $prefCurrency)
                    ];
                }
            } else {
                $builder = $db->table('views')
                    ->select('viewable_id as id, viewable_type as type, COUNT(id) as period_views, SUM(watch_duration) as period_watchtime')
                    ->where('creator_id', $userId)->where('created_at >=', $startDate)
                    ->groupBy('viewable_id, viewable_type');

                if ($type === 'watchTime' || $type === 'watchtime') {
                    $builder->orderBy('period_watchtime', 'DESC');
                } elseif ($type === 'impressions') {
                    $builder = $db->table('impressions')
                        ->select('impressionable_id as id, impressionable_type as type, COUNT(id) as period_impressions')
                        ->where('creator_id', $userId)->where('created_at >=', $startDate)
                        ->groupBy('impressionable_id, impressionable_type')
                        ->orderBy('period_impressions', 'DESC');
                } else {
                    $builder->orderBy('period_views', 'DESC');
                }

                $topStats = $builder->limit($contentLimit)->get()->getResultArray();

                foreach ($topStats as $stat) {
                    $cType = strtolower($stat['type']);
                    $table = ($cType === 'reel') ? 'reels' : 'videos';
                    $titleColumn = ($cType === 'reel') ? 'caption as title' : 'title';
                    $content = $db->table($table)->select($titleColumn . ', thumbnail_url, status')->where('id', $stat['id'])->get()->getRow();
                    
                    if (!$content) continue;

                    $itemRevenueRaw = $db->table('creator_earnings')
                        ->selectSum('amount', 'total')
                        ->where('content_id', $stat['id'])->where('content_type', $cType)->where('created_at >=', $startDate)
                        ->get()->getRow()->total ?? 0;

                    if ($type === 'impressions') {
                        $itemImpressions = (int)$stat['period_impressions'];
                        $vStats = $db->table('views')->select('COUNT(id) as period_views, SUM(watch_duration) as period_watchtime')
                            ->where('viewable_id', $stat['id'])->where('viewable_type', $cType)->where('created_at >=', $startDate)->get()->getRow();
                        $pViews = (int)($vStats->period_views ?? 0);
                        $pWatchTime = (int)($vStats->period_watchtime ?? 0);
                    } else {
                        $pViews = (int)$stat['period_views'];
                        $pWatchTime = (int)$stat['period_watchtime'];
                        $itemImpressions = $db->table('impressions')
                            ->where('impressionable_id', $stat['id'])->where('impressionable_type', $cType)
                            ->where('created_at >=', $startDate)->countAllResults();
                    }

                    $formattedRecent[] = [
                        'id'          => (string)$stat['id'],
                        'type'        => $cType,
                        'title'       => !empty($content->title) ? (strlen($content->title) > 25 ? substr($content->title, 0, 25) . '...' : $content->title) : "Untitled",
                        'views'       => $pViews,
                        'thumbnail'   => !empty($content->thumbnail_url) ? (function_exists('get_media_url') ? get_media_url($content->thumbnail_url) : $content->thumbnail_url) : 'default.jpg',
                        'status'      => strtoupper($content->status ?? 'PUBLISHED'),
                        'watchTime'   => round($pWatchTime / 3600, 2),
                        'followers'   => "No data", 
                        'impressions' => $itemImpressions,
                        'ctr'         => $itemImpressions > 0 ? round(($pViews / $itemImpressions) * 100, 1) : 0,
                        'revenue'     => get_currency_object($itemRevenueRaw, $prefCurrency)
                    ];
                }
            }

            // ==========================================
            // 📉 4. CONTENT METRICS & CTR 
            // ==========================================
            $ctrValue = $totalImpressions > 0 ? round(($totalViews / $totalImpressions) * 100, 1) : 0;
            $avgDurationMins = $totalViews > 0 ? floor($watchDuration / $totalViews / 60) : 0;
            $avgDurationSecs = $totalViews > 0 ? ($watchDuration / $totalViews) % 60 : 0;

            // ==========================================
            // 🚦 5. TRAFFIC SOURCES 
            // ==========================================
            $trafficQuery = $db->table('impressions')
                ->select('traffic_source, COUNT(id) as count')
                ->where('creator_id', $userId)
                ->where('created_at >=', $startDate)
                ->groupBy('traffic_source')
                ->get()->getResultArray();

            $trafficSources = [];
            $totalTrafficImpressions = array_sum(array_column($trafficQuery, 'count')) ?: 1;
            foreach ($trafficQuery as $t) {
                $name = str_replace('_', ' ', ucfirst($t['traffic_source'] ?? 'Direct'));
                $trafficSources[] = [
                    'name'    => $name,
                    'percent' => round(($t['count'] / $totalTrafficImpressions) * 100, 1)
                ];
            }

            // ==========================================
            // 👥 6. AUDIENCE DEMOGRAPHICS
            // ==========================================
            $audienceQuery = $db->table('views v')
                ->select('u.gender, u.country, u.dob') 
                ->join('users u', 'u.id = v.user_id', 'inner')
                ->where('v.creator_id', $userId)
                ->where('v.user_id IS NOT NULL')
                ->where('v.created_at >=', $startDate)
                ->get()->getResultArray();

            $male = 0; $female = 0; $countries = []; $ages = ['13-17' => 0, '18-24' => 0, '25-34' => 0, '35+' => 0];
            
            foreach($audienceQuery as $viewer) {
                if(strtolower($viewer['gender'] ?? '') === 'male') $male++;
                if(strtolower($viewer['gender'] ?? '') === 'female') $female++;
                
                $loc = (!empty($viewer['country']) && strlen(trim($viewer['country'])) > 1) ? trim($viewer['country']) : 'Unknown';
                if(!isset($countries[$loc])) $countries[$loc] = 0;
                $countries[$loc]++;
                
                if (!empty($viewer['dob'])) {
                    $age = date_diff(date_create($viewer['dob']), date_create('today'))->y;
                    if ($age >= 13 && $age <= 17) $ages['13-17']++;
                    elseif ($age >= 18 && $age <= 24) $ages['18-24']++;
                    elseif ($age >= 25 && $age <= 34) $ages['25-34']++;
                    elseif ($age >= 35) $ages['35+']++;
                }
            }
            $totalAudience = count($audienceQuery) ?: 1;
            $geographies = [];
            arsort($countries);
            $countCountry = 0;
            foreach($countries as $country => $c) {
                if($countCountry++ >= 4) break; 
                $geographies[] = ['country' => $country, 'percent' => round(($c / $totalAudience) * 100, 1)];
            }
            $ageGroups = [];
            foreach($ages as $range => $countAge) {
                if ($countAge > 0) {
                    $ageGroups[] = ['range' => $range . ' years', 'percent' => round(($countAge / $totalAudience) * 100, 1)];
                }
            }

            // ==========================================
            // 💰 7. REVENUE & EARNINGS
            // ==========================================
            $recentRevenueRow = $db->table('creator_earnings')
                ->selectSum('amount', 'total_amount')
                ->where('user_id', $userId)
                ->where('created_at >=', $startDate)
                ->get()->getRow();
            $recentRevenueAmount = ($recentRevenueRow && isset($recentRevenueRow->total_amount)) ? $recentRevenueRow->total_amount : 0;

            $monthlyQuery = $db->table('creator_earnings')
                ->select('MONTHNAME(created_at) as month, SUM(amount) as total')
                ->where('user_id', $userId)
                ->groupBy('MONTH(created_at), MONTHNAME(created_at)')
                ->orderBy('MONTH(created_at)', 'DESC')
                ->limit(3)
                ->get()->getResultArray();

            $monthlyRevenue = [];
            foreach ($monthlyQuery as $m) {
                $monthlyRevenue[] = [
                    'month'   => $m['month'],
                    'amount'  => get_currency_object($m['total'], $prefCurrency)['display'], 
                    'percent' => 0 
                ];
            }

            // ==========================================
            // ⏱️ 8. REALTIME BARS
            // ==========================================
            $time48hAgo = date('Y-m-d H:i:s', strtotime('-48 hours'));
            $time60mAgo = date('Y-m-d H:i:s', strtotime('-60 minutes'));
            $now = time();

            $v48Count = $db->table('views')->where('creator_id', $userId)->where('created_at >=', $time48hAgo)->countAllResults();
            $v60Count = $db->table('views')->where('creator_id', $userId)->where('created_at >=', $time60mAgo)->countAllResults();

            $c48Query = $db->table('views')->select('HOUR(created_at) as hr, DATE(created_at) as dt, COUNT(id) as vc')
                ->where('creator_id', $userId)->where('created_at >=', $time48hAgo)
                ->groupBy('DATE(created_at), HOUR(created_at)')->get()->getResultArray();
            $bar48h = array_fill(0, 48, 0);
            foreach($c48Query as $r) {
                $viewTime = strtotime($r['dt'].' '.$r['hr'].':00:00');
                $diff = floor(($now - $viewTime) / 3600);
                if($diff >= 0 && $diff < 48) $bar48h[47 - $diff] = (int)$r['vc'];
            }

            $c60Query = $db->table('views')->select('MINUTE(created_at) as mn, HOUR(created_at) as hr, DATE(created_at) as dt, COUNT(id) as vc')
                ->where('creator_id', $userId)->where('created_at >=', $time60mAgo)
                ->groupBy('DATE(created_at), HOUR(created_at), MINUTE(created_at)')->get()->getResultArray();
            $bar60m = array_fill(0, 60, 0);
            foreach($c60Query as $r) {
                $viewTime = strtotime($r['dt'].' '.$r['hr'].':'.$r['mn'].':00');
                $mDiff = floor(($now - $viewTime) / 60);
                if($mDiff >= 0 && $mDiff < 60) $bar60m[59 - $mDiff] = (int)$r['vc'];
            }

            // ==========================================
            // 📦 9. BUILD FINAL JSON RESPONSE
            // ==========================================
            $isMonetized = ($channel && isset($channel->is_monetization_enabled)) ? (bool)$channel->is_monetization_enabled : false;

            $response = [
                'user' => [
                    'isMonetized' => $isMonetized,
                    'currency' => $prefCurrency 
                ],
                'performance' => [
                    'views'      => (int)$totalViews, 
                    'impressions'=> (int)$totalImpressions, 
                    'ctr'        => $ctrValue,         
                    'watchTime'  => $formattedWatchTime,
                    'followers'  => (int)$totalFollowers, 
                    'newFollowers' => '+' . $newFollowersGained, 
                    'engagement' => $engagementRate . '%',
                    'chartData'  => $realChartData 
                ],
                'recentContent' => $formattedRecent, 
                'content' => [
                    'metrics' => [
                        'impressions' => (int)$totalImpressions,
                        'ctr'         => $ctrValue . '%',
                        'avgDuration' => sprintf("%d:%02d", $avgDurationMins, $avgDurationSecs)
                    ],
                    'trafficSources' => empty($trafficSources) ? [['name' => 'Direct', 'percent' => 100]] : $trafficSources
                ],
                'audience' => [
                    'gender'      => [
                        'male'   => round(($male / $totalAudience) * 100, 1),
                        'female' => round(($female / $totalAudience) * 100, 1)
                    ],
                    'ageGroups'   => $ageGroups,
                    'geographies' => $geographies
                ],
                'revenue' => [
                    'estimated'  => get_currency_object($recentRevenueAmount, $prefCurrency), 
                    'changeText' => "Total earnings in last {$period}",
                    'chartData'  => $realChartData, 
                    'monthly'    => $monthlyRevenue
                ],
                'realtime' => [
                    'views_48h'  => $v48Count,
                    'views_60m'  => $v60Count,
                    'chart_48h'  => $bar48h, 
                    'chart_60m'  => $bar60m, 
                    'topContent_48h' => $this->getRealtimeTopContent($db, $userId, $time48hAgo),
                    'topContent_60m' => $this->getRealtimeTopContent($db, $userId, $time60mAgo)
                ],
                'realtimeViews' => $v48Count 
            ];

            return $this->respond($response);

        } catch (\Exception $e) {
            return $this->failServerError('Error fetching analytics: ' . $e->getMessage());
        }
    }

    private function getStartDate($period) {
        switch (strtoupper($period)) {
            case '7D': return date('Y-m-d H:i:s', strtotime('-7 days'));
            case '90D': return date('Y-m-d H:i:s', strtotime('-90 days'));
            case '365D': return date('Y-m-d H:i:s', strtotime('-365 days'));
            case 'LIFE': return '2000-01-01 00:00:00'; 
            case '28D':
            default: return date('Y-m-d H:i:s', strtotime('-28 days'));
        }
    }

    /**
     * 🔥 FIXED: 100% SYNCED VIDEO/REEL ANALYTICS
     */
    public function getVideoAnalytics($id, $type)
    {
        try {
            $db = \Config\Database::connect();
            date_default_timezone_set('Asia/Kolkata');
            $userId = $this->request->getHeaderLine('User-ID');
            $type = strtolower($type); // video or reel

            $period = $this->request->getGet('period') ?? '28D';
            $startDate = $this->getStartDate($period);
            $time48hAgo = date('Y-m-d H:i:s', strtotime('-48 hours'));
            
            $daysCount = (int)filter_var($period, FILTER_SANITIZE_NUMBER_INT) ?: 28;

            $table = ($type === 'reel') ? 'reels' : 'videos';
            $titleCol = ($type === 'reel') ? 'caption as title' : 'title';

            // 🔥 FIX 1: GET SYNCED VIEWS DIRECTLY FROM VIEWS TABLE
            $totalViews = $db->table('views')
                ->where(['viewable_id' => $id, 'viewable_type' => $type])
                ->countAllResults();

            // 🔥 FIX 2: GET SYNCED IMPRESSIONS DIRECTLY FROM IMPRESSIONS TABLE
            $totalImpressions = $db->table('impressions')
                ->where(['impressionable_id' => $id, 'impressionable_type' => $type])
                ->countAllResults();

            // 1. CONTENT META (Fetched from content table)
            $content = $db->table($table)
                ->select($titleCol . ', thumbnail_url, visibility, status, copyright_status, created_at, likes_count, comments_count, shares_count, duration')
                ->where('id', $id)
                ->get()
                ->getRow();

            if (!$content) return $this->failNotFound('Content not found');

            // 2. WATCH TIME & AVD LOGIC
            $viewStats = $db->table('views')
                ->selectSum('watch_duration', 'total_watchtime')
                ->where(['viewable_id' => $id, 'viewable_type' => $type])
                ->get()
                ->getRow();

            $totalWatchTimeSec = (float)($viewStats->total_watchtime ?? 0);
            $watchTimeHrs = round($totalWatchTimeSec / 3600, 2);

            $avdSeconds = ($totalViews > 0) ? ($totalWatchTimeSec / $totalViews) : 0;
            $vidDuration = (int)($content->duration ?? 0);
            $avgRetention = ($vidDuration > 0) ? round(($avdSeconds / $vidDuration) * 100, 1) : 0;

            // 🔥 FIX 3: CTR CALCULATION
            $ctr = ($totalImpressions > 0) ? round(($totalViews / $totalImpressions) * 100, 1) : 0;

            // 3. REAL-TIME 48H DATA
            $views48h = $db->table('views')
                ->where(['viewable_id' => $id, 'viewable_type' => $type, 'created_at >=' => $time48hAgo])
                ->countAllResults();

            $c48Query = $db->table('views')->select('HOUR(created_at) as hr, DATE(created_at) as dt, COUNT(id) as vc')
                ->where(['viewable_id' => $id, 'viewable_type' => $type, 'created_at >=' => $time48hAgo])
                ->groupBy('DATE(created_at), HOUR(created_at)')->get()->getResultArray();
            
            $now = time();
            $bar48h = array_fill(0, 48, 0);
            foreach($c48Query as $r) {
                $viewTime = strtotime($r['dt'].' '.$r['hr'].':00:00');
                $diff = floor(($now - $viewTime) / 3600);
                if($diff >= 0 && $diff < 48) $bar48h[47 - $diff] = (int)$r['vc'];
            }

            // 4. TRAFFIC SOURCES
            $trafficQuery = $db->table('impressions')
                ->select('traffic_source, COUNT(id) as count')
                ->where(['impressionable_id' => $id, 'impressionable_type' => $type, 'created_at >=' => $startDate])
                ->groupBy('traffic_source')->orderBy('count', 'DESC')->limit(4)->get()->getResultArray();

            $trafficSources = [];
            $totalTrafficSum = array_sum(array_column($trafficQuery, 'count')) ?: 1;
            foreach ($trafficQuery as $t) {
                $trafficSources[] = [
                    'name' => !empty($t['traffic_source']) ? ucfirst(str_replace('_', ' ', $t['traffic_source'])) : 'Direct/Unknown',
                    'percent' => round(($t['count'] / $totalTrafficSum) * 100, 1)
                ];
            }

            // 5. AUDIENCE
            $audienceQuery = $db->table('views v')
                ->select('u.gender, u.country, u.dob')->join('users u', 'u.id = v.user_id', 'inner')
                ->where(['v.viewable_id' => $id, 'v.viewable_type' => $type, 'v.created_at >=' => $startDate])
                ->get()->getResultArray();

            $male = 0; $female = 0; $countries = [];
            foreach($audienceQuery as $viewer) {
                if(strtolower($viewer['gender'] ?? '') === 'male') $male++;
                if(strtolower($viewer['gender'] ?? '') === 'female') $female++;
                $loc = trim($viewer['country'] ?? 'Unknown') ?: 'Unknown';
                $countries[$loc] = ($countries[$loc] ?? 0) + 1;
            }
            $totalAud = count($audienceQuery) ?: 1;
            $geog = []; arsort($countries); $countC = 0;
            foreach($countries as $country => $c) {
                if($countC++ >= 4) break; 
                $geog[] = ['country' => $country, 'percent' => round(($c / $totalAud) * 100, 1)];
            }

            // 6. CHART DATA
            $graphQuery = $db->table('views')->select('DATE(created_at) as date, COUNT(id) as count')
                ->where(['viewable_id' => $id, 'viewable_type' => $type, 'created_at >=' => $startDate])
                ->groupBy('DATE(created_at)')->get()->getResultArray();
            $dailyData = [];
            foreach($graphQuery as $row) { $dailyData[$row['date']] = (int)$row['count']; }

            $chartPoints = [];
            $maxV = count($dailyData) > 0 ? max(array_values($dailyData)) : 1; 
            for ($i = $daysCount; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $val = $dailyData[$date] ?? 0;
                $x = round(($daysCount - $i) * (150 / $daysCount));
                $y = round(90 - (($val / $maxV) * 80));
                $chartPoints[] = "$x,$y";
            }

            $revenueAmount = $db->table('creator_earnings')->selectSum('amount')->where(['content_id' => $id, 'content_type' => $type])->get()->getRow()->amount ?? 0;

            return $this->respond([
                'success' => true,
                'data' => [
                    'meta' => [
                        'title' => $content->title,
                        'thumbnail' => $content->thumbnail_url,
                        'visibility' => $content->visibility,
                        'status' => strtoupper($content->status ?? 'PUBLISHED'),
                        'copyright_status' => $content->copyright_status,
                        'created_at' => $content->created_at,
                        'views' => $totalViews,
                        'views_48h' => $views48h, 
                        'watch_time' => $watchTimeHrs,
                        'impressions' => $totalImpressions, 
                        'ctr' => $ctr,
                        'engagement_rate' => $totalViews > 0 ? round((($content->likes_count + $content->comments_count) / $totalViews) * 100, 1) : 0,
                        'avd' => sprintf("%d:%02d", floor($avdSeconds/60), $avdSeconds%60),
                        'avg_retention' => $avgRetention, 
                        'likes' => (int)$content->likes_count,
                        'comments' => (int)$content->comments_count,
                        'shares' => (int)$content->shares_count,
                        'revenue' => $revenueAmount
                    ],
                    'daily_stats' => [ 'chart_points' => implode(" ", $chartPoints), 'max_value' => $maxV ],
                    'trafficSources' => empty($trafficSources) ? [['name' => 'Direct/Unknown', 'percent' => 100]] : $trafficSources, 
                    'audience' => [ 
                        'gender' => [ 'male' => round(($male / $totalAud) * 100, 1), 'female' => round(($female / $totalAud) * 100, 1) ],
                        'geographies' => $geog
                    ],
                    'realtime' => [ 'chart_48h' => $bar48h ]
                ]
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function getRealtimeTopContent($db, $userId, $startTime) {
        $stats = $db->table('views')
            ->select('viewable_id as id, viewable_type as type, COUNT(id) as views')
            ->where(['creator_id' => $userId, 'created_at >=' => $startTime])
            ->groupBy('viewable_id, viewable_type')
            ->orderBy('views', 'DESC')
            ->limit(5)->get()->getResultArray();
            
        $result = [];
        foreach($stats as $s) {
            $table = ($s['type'] === 'reel') ? 'reels' : 'videos';
            $titleCol = ($s['type'] === 'reel') ? 'caption as title' : 'title';
            $content = $db->table($table)->select($titleCol . ', thumbnail_url')->where('id', $s['id'])->get()->getRow();
            
            if($content) {
                $result[] = [
                    'id' => (string)$s['id'],
                    'type' => $s['type'],
                    'title' => $content->title ?? 'Untitled',
                    'thumbnail' => $content->thumbnail_url,
                    'views' => (int)$s['views']
                ];
            }
        }
        return $result;
    }
}
