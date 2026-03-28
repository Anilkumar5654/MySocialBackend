<?php namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Throwable;
use DateTime;

class DashboardController extends BaseController
{
    use ResponseTrait;
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        helper(['text', 'number', 'media', 'url', 'currency', 'date']); 
    }

    /**
     * 🔥 Helper: Convert Timestamp to "Time Ago" format
     */
    private function time_ago($timestamp) {
        $time_ago = strtotime($timestamp);
        $cur_time = time();
        $time_elapsed = $cur_time - $time_ago;
        $seconds = $time_elapsed;
        $minutes = round($time_elapsed / 60);
        $hours   = round($time_elapsed / 3600);
        $days    = round($time_elapsed / 86400);
        $weeks   = round($time_elapsed / 604800);
        $months  = round($time_elapsed / 2600640);
        $years   = round($time_elapsed / 31207680);

        if ($seconds <= 60) return "Just now";
        else if ($minutes <= 60) return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
        else if ($hours <= 24) return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
        else if ($days <= 7) return ($days == 1) ? "Yesterday" : "$days days ago";
        else if ($weeks <= 4.3) return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
        else if ($months <= 12) return ($months == 1) ? "1 month ago" : "$months months ago";
        else return ($years == 1) ? "1 year ago" : "$years years ago";
    }

    private function calculateTier($score) {
        if ($score >= 90) return "Trusted Creator";
        if ($score >= 70) return "Normal Creator";
        if ($score >= 50) return "Risky Creator";
        return "Restricted Creator";
    }

    public function index() {
        try {
            $userId = $this->request->getHeaderLine('User-ID') ?: $this->request->getHeaderLine('user-id'); 
            if (!$userId) return $this->failUnauthorized('User ID missing.');

            $channel = $this->db->table('channels')
                            ->select('channels.*, users.kyc_status, users.followers_count, users.preferred_currency')
                            ->join('users', 'users.id = channels.user_id')
                            ->where('channels.user_id', $userId)
                            ->get()->getRow();

            if (!$channel) return $this->failNotFound('Channel not found.');
            
            $prefCurrency = $channel->preferred_currency ?? 'INR';
            $activeStrikes = $this->db->table('channel_strikes')->where(['channel_id' => $channel->id, 'status' => 'ACTIVE', 'type' => 'STRIKE'])->countAllResults();
            $activeWarnings = $this->db->table('channel_strikes')->where(['channel_id' => $channel->id, 'status' => 'ACTIVE', 'type' => 'WARNING'])->countAllResults();
            $isMonetized = ($channel->monetization_status === 'APPROVED' && $channel->is_monetization_enabled == 1);

            $perf = $this->getGlobalPerformance($userId);
            $recentVideos = $this->getMinimalRecent($userId);

            // Latest Comment with Time Ago
            $lastComment = $this->db->table('comments c')
                ->select('c.content, c.created_at, u.username as sender_name, u.avatar as sender_avatar, 
                          COALESCE(v.title, r.caption) as content_title, 
                          COALESCE(v.thumbnail_url, r.thumbnail_url) as content_thumbnail')
                ->join('users u', 'u.id = c.user_id')
                ->join('videos v', 'v.id = c.commentable_id AND c.commentable_type = "video"', 'left')
                ->join('reels r', 'r.id = c.commentable_id AND c.commentable_type = "reel"', 'left')
                ->where('(v.user_id = '.$userId.' OR r.user_id = '.$userId.')')
                ->orderBy('c.created_at', 'DESC')
                ->get()
                ->getRow();

            if ($lastComment) {
                $lastComment->sender_avatar = get_media_url($lastComment->sender_avatar, 'avatar');
                $lastComment->content_thumbnail = get_media_url($lastComment->content_thumbnail, 'video_thumb');
                $lastComment->created_at = $this->time_ago($lastComment->created_at); // 🔥 Formatting Comment Time
            }

            return $this->respond([
                'status' => 'success',
                'user' => [
                    'name' => $channel->name,
                    'username' => $channel->handle,
                    'avatar' => $channel->avatar,
                    'tier' => $this->calculateTier((int)$channel->trust_score),
                    'trustScore' => (int)$channel->trust_score,
                    'kycStatus' => $channel->kyc_status, 
                    'monetizationStatus' => $channel->monetization_status, 
                    'strikesCount' => (int)$activeStrikes,
                    'warningsCount' => (int)$activeWarnings,
                    'isMonetized' => (bool)$isMonetized,
                ],
                'channel' => ['id' => (int)$channel->id, 'unique_id' => $channel->unique_id],
                'performance' => [
                    'views' => (int)$perf['views'],
                    'qualifiedViews' => (int)$this->getQualifiedViews($userId),
                    'followers' => (int)($channel->followers_count ?? 0),
                    'impressions' => (int)$perf['impressions'],
                    'watchTime' => (float)$perf['watchTime']
                ],
                'recent_videos' => $recentVideos,
                'lastComment' => $lastComment,
                'wallet' => ['currency' => (string)$prefCurrency]
            ]);
        } catch (Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    private function getGlobalPerformance($userId) {
        $stats = $this->db->table('views')
            ->selectCount('id', 'v')
            ->selectSum('watch_duration', 'wd')
            ->where('creator_id', $userId)
            ->get()->getRow();

        $totalViews = (int)($stats->v ?? 0);
        $watchTimeHrs = round(($stats->wd ?? 0) / 3600, 2);

        $totalImpressions = $this->db->table('impressions')->where('creator_id', $userId)->countAllResults();
        if ($totalImpressions == 0) {
            $vImp = $this->db->table('videos')->selectSum('impressions_count', 'i')->where('user_id', $userId)->get()->getRow()->i ?? 0;
            $rImp = $this->db->table('reels')->selectSum('impressions_count', 'i')->where('user_id', $userId)->get()->getRow()->i ?? 0;
            $totalImpressions = $vImp + $rImp;
        }

        return ['views' => $totalViews, 'impressions' => $totalImpressions, 'watchTime' => $watchTimeHrs];
    }

    private function getMinimalRecent($userId) {
        $videoQuery = $this->db->table('videos')
            ->select('id, title, thumbnail_url as thumbnail, views_count as views, comments_count, likes_count, status, \'video\' as type, created_at, duration')
            ->where(['user_id' => $userId, 'status' => 'published', 'visibility' => 'public'])
            ->getCompiledSelect();

        $reelQuery = $this->db->table('reels')
            ->select('id, caption as title, thumbnail_url as thumbnail, views_count as views, comments_count, likes_count, status, \'reel\' as type, created_at, duration')
            ->where(['user_id' => $userId, 'status' => 'published', 'visibility' => 'public'])
            ->getCompiledSelect();

        $results = $this->db->query("$videoQuery UNION $reelQuery ORDER BY created_at DESC LIMIT 3")->getResultArray();
        
        foreach ($results as &$vid) { 
            $vid['status'] = strtoupper($vid['status']); 
            $vid['thumbnail'] = get_media_url($vid['thumbnail'], 'video_thumb');
            $vid['views'] = (int)$vid['views'];
            $vid['comments_count'] = (int)($vid['comments_count'] ?? 0);
            $vid['likes_count'] = (int)($vid['likes_count'] ?? 0);
            
            // 🔥 Time Ago formatting for frontend published_at key
            $vid['published_at'] = $this->time_ago($vid['created_at']); 
        }
        return $results;
    }

    private function getQualifiedViews($userId) {
        $row = $this->db->table('creator_daily_points')->selectSum('qualified_views')->where('user_id', $userId)->get()->getRow();
        return (int)($row->qualified_views ?? 0);
    }
}
