<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\HashtagHelper;

class ContentController extends BaseController
{
    use ResponseTrait;
    protected $db;
    protected $hashtagHelper;

    public function __construct() {
        $this->db = \Config\Database::connect();
        $this->hashtagHelper = new HashtagHelper();
        helper(['text', 'filesystem', 'media', 'url', 'form', 'number']);
    }

    /**
     * ✅ 1. LIST CONTENT (Video + Reels Unified) - No Changes
     */
    public function index()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->respond(['success' => false, 'error' => 'Auth failed'], 401);

        $type   = $this->request->getGet('type') ?? 'all'; 
        $filter = $this->request->getGet('status') ?? 'all'; 
        $search = trim($this->request->getGet('search') ?? '');
        $page   = (int)($this->request->getGet('page') ?? 1);
        $limit  = (int)($this->request->getGet('limit') ?? 10);
        $offset = ($page - 1) * $limit;

        $videoFields = "v.id, v.title AS title, v.views_count AS views, v.status, v.visibility, v.scheduled_at, v.thumbnail_url AS thumbnail, v.video_url, v.duration, v.monetization_enabled, v.copyright_status, v.created_at, 'video' AS content_type";
        $reelFields  = "r.id, r.caption AS title, r.views_count AS views, r.status, r.visibility, r.scheduled_at, r.thumbnail_url AS thumbnail, r.video_url, r.duration, r.monetization_enabled, r.copyright_status, r.created_at, 'reel' AS content_type"; 

        try {
            if ($type === 'all') {
                $videoBuilder = $this->db->table('videos v')->select($videoFields)->where('v.user_id', $userId);
                $reelBuilder  = $this->db->table('reels r')->select($reelFields)->where('r.user_id', $userId);
                $this->applySmartFilter($videoBuilder, $filter);
                $this->applySmartFilter($reelBuilder, $filter);

                if (!empty($search)) {
                    $videoBuilder->like('v.title', $search);
                    $reelBuilder->like('r.caption', $search);
                }

                $unionQuery = "(" . $videoBuilder->getCompiledSelect() . ") UNION (" . $reelBuilder->getCompiledSelect() . ")";
                $total = $this->db->query("SELECT COUNT(*) as total FROM ($unionQuery) AS combined")->getRow()->total ?? 0;
                $items = $this->db->query("$unionQuery ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->getResultArray();
            } else {
                $table = ($type === 'reel') ? 'reels' : 'videos';
                $fields = str_replace(['v.', 'r.'], 't.', ($type === 'reel' ? $reelFields : $videoFields));
                $builder = $this->db->table($table . ' AS t')->select($fields)->where('t.user_id', $userId);
                $this->applySmartFilter($builder, $filter);
                if (!empty($search)) $builder->like(($type === 'reel' ? "t.caption" : "t.title"), $search);
                $total = $builder->countAllResults(false);
                $items = $builder->orderBy('t.created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();
            }

            foreach ($items as &$item) {
                $item['thumbnail'] = get_media_url($item['thumbnail'] ?? '');
                $item['views'] = (int)$item['views'];
                $item['monetization_enabled'] = (int)($item['monetization_enabled'] ?? 0); 
                $item['strikes'] = $this->getContentStrikes($item['id'], strtoupper($item['content_type']));
            }

            return $this->respond(['success' => true, 'items' => $items, 'meta' => ['total' => (int)$total, 'hasMore' => ($total > ($offset + $limit))]]);
        } catch (\Exception $e) { return $this->respond(['success' => false, 'error' => $e->getMessage()], 500); }
    }

    /**
     * ✅ 2. GET DETAILS - No Changes
     */
    public function details($id = null)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $type = $this->request->getGet('type') ?? 'video';
        $table = ($type === 'reel') ? 'reels' : 'videos';
        $content = $this->db->table($table)->select($type === 'reel' ? "*, caption AS title" : "*")->where(['id' => $id, 'user_id' => $userId])->get()->getRowArray();
        if (!$content) return $this->failNotFound();

        $tagsData = $this->db->table('taggables t')->select('h.tag')->join('hashtags h', 'h.id = t.hashtag_id')->where(['t.taggable_id' => $id, 't.taggable_type' => $type])->get()->getResultArray();
        $content['tags'] = implode(', ', array_column($tagsData, 'tag'));
        $content['thumbnail'] = get_media_url($content['thumbnail_url'] ?? ''); 
        $content['video_url'] = get_media_url($content['video_url'] ?? '');
        $content['strikes'] = $this->getContentStrikes($id, strtoupper($type));

        return $this->respond(['success' => true, 'meta' => $content]);
    }

    /**
     * ✅ 3. ANALYTICS (REAL CTR & REAL RETENTION)
     */
    public function analytics($id = null)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();

        $type = $this->request->getGet('type') ?? 'video';
        $table = ($type === 'reel') ? 'reels' : 'videos';
        
        $content = $this->db->table($table)->where(['id' => $id, 'user_id' => $userId])->get()->getRowArray();
        if (!$content) return $this->respond(['success' => false, 'error' => 'Data missing'], 404);

        // Core Metrics
        $views = (int)($content['views_count'] ?? 0);
        $impressions = (int)($content['impressions_count'] ?? 0);
        $likes = (int)($content['likes_count'] ?? 0);
        $comments = (int)($content['comments_count'] ?? 0);
        $shares = (int)($content['shares_count'] ?? 0);

        // 1. Followers Sync
        $userRow = $this->db->table('users')->select('followers_count')->where('id', $userId)->get()->getRow();
        $totalFollowers = $userRow ? (int)$userRow->followers_count : 0;

        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $newFollowers = $this->db->table('follows')->where('following_id', $userId)->where('created_at >=', $sevenDaysAgo)->countAllResults();

        // 🔥 2. REAL RETENTION & WATCH TIME (Using video_watch_sessions)
        $watchData = $this->db->table('video_watch_sessions')
            ->selectSum('watch_duration', 'total_sec')
            ->selectAvg('completion_rate', 'avg_rate')
            ->where(['video_id' => $id, 'video_type' => $type])
            ->get()->getRow();
        
        $totalWatchSec = (int)($watchData->total_sec ?? 0);
        $watchTimeHours = round($totalWatchSec / 3600, 2);
        
        // Retention percentage (0-100)
        $avgRetention = round($watchData->avg_rate ?? 0, 1);
        
        // Average Duration (How long a user stays in mm:ss)
        $avgDurationSeconds = ($views > 0) ? ($totalWatchSec / $views) : 0;
        $avgDurationStr = gmdate($avgDurationSeconds >= 3600 ? "H:i:s" : "i:s", $avgDurationSeconds);

        // 3. REVENUE
        $earningRow = $this->db->table('creator_earnings')->selectSum('amount')->where(['user_id' => $userId, 'content_id' => $id, 'content_type' => $type])->get()->getRow();
        $revenue = $earningRow->amount ? (float)$earningRow->amount : 0.00;

        // 🔥 4. REAL CTR & REACH
        $ctr = ($impressions > 0) ? round(($views / $impressions) * 100, 2) : 0;
        $reach = $impressions;
        $engagementRate = ($views > 0) ? round((($likes + $comments + $shares) / $views) * 100, 1) : 0;

        return $this->respond([
            'success' => true,
            'data' => [
                'meta' => [
                    'id' => (int)$content['id'],
                    'title' => $content['title'] ?? $content['caption'] ?? 'Untitled',
                    'thumbnail' => get_media_url($content['thumbnail_url']),
                    'views' => $views,
                    'likes' => $likes,
                    'comments' => $comments,
                    'shares' => $shares,
                    'reach' => $reach,
                    'watch_time' => $watchTimeHours,
                    'new_followers' => $newFollowers,
                    'total_followers' => $totalFollowers,
                    'revenue' => round($revenue, 2),
                    'engagement_rate' => $engagementRate,
                    'ctr' => $ctr, 
                    'avg_retention' => $avgRetention, // 🔥 REAL FROM DB
                    'avg_duration' => $avgDurationStr, // 🔥 REAL FROM DB
                    'status' => strtoupper($content['status']),
                    'visibility' => $content['visibility'],
                    'copyright_status' => $content['copyright_status'],
                    'created_at' => $content['created_at'],
                ],
                'daily_stats' => [
                    'max_value' => $this->getMaxGraphValue($id, $type),
                    'stats' => $this->getOptimizedGraphStats($id, $type)
                ]
            ]
        ]);
    }

    /**
     * ✅ 4. UPDATE - No Changes
     */
    public function update()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $input = $this->request->getPost();
        $id = $input['id'] ?? null;
        $type = $input['type'] ?? 'video';
        $table = ($type === 'reel') ? 'reels' : 'videos';

        $exists = $this->db->table($table)->where(['id' => $id, 'user_id' => $userId])->get()->getRow();
        if (!$exists) return $this->failNotFound();

        $updateData = [];
        $fields = ['visibility', 'category', 'allow_comments', 'monetization_enabled'];
        foreach($fields as $f) if ($this->request->getPost($f) !== null) $updateData[$f] = $this->request->getPost($f);

        if ($type === 'video') {
            $updateData['title'] = $this->request->getPost('title');
            $updateData['description'] = $this->request->getPost('description');
        } else {
            $updateData['caption'] = $this->request->getPost('caption');
        }

        $file = $this->request->getFile('thumbnail');
        if ($file && $file->isValid()) {
            if (!empty($exists->thumbnail_url)) delete_media_master($exists->thumbnail_url);
            $updateData['thumbnail_url'] = upload_media_master($file, $type . '_thumbnail');
        }

        if ($this->db->table($table)->where('id', $id)->update($updateData)) {
            $rawTags = $this->request->getPost('tags');
            if (!empty($rawTags)) $this->hashtagHelper->sync($type, $id, array_map('trim', explode(',', $rawTags))); 
            return $this->respond(['success' => true, 'message' => 'Updated']);
        }
        return $this->failServerError();
    }

    /**
     * ✅ 5. DELETE - No Changes
     */
    public function delete()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        $input = $this->request->getJSON(true) ?: $this->request->getPost();
        $id = $input['id'] ?? null;
        $type = $input['type'] ?? 'video';
        $table = ($type === 'reel') ? 'reels' : 'videos';
        $content = $this->db->table($table)->where(['id' => $id, 'user_id' => $userId])->get()->getRow();

        if ($content) {
            if(!empty($content->video_url)) delete_media_master($content->video_url);
            if(!empty($content->thumbnail_url)) delete_media_master($content->thumbnail_url);
            $this->db->table($table)->where('id', $id)->delete();
            return $this->respond(['success' => true]);
        }
        return $this->failNotFound();
    }

    private function getContentStrikes($id, $type) {
        return $this->db->table('channel_strikes s')
            ->select('s.*, v.title as orig_v_title, v.thumbnail_url as orig_v_thumb, r.caption as orig_r_title, r.thumbnail_url as orig_r_thumb, cv.name as v_owner, cr.name as r_owner')
            ->join('videos v', 'v.id = s.original_content_id', 'left')->join('channels cv', 'cv.id = v.channel_id', 'left')
            ->join('reels r', 'r.id = s.original_content_id', 'left')->join('channels cr', 'cr.id = r.channel_id', 'left')
            ->where(['s.content_type' => $type, 's.content_id' => $id, 's.status' => 'ACTIVE'])->get()->getResultArray();
    }

    private function applySmartFilter(&$builder, $filter) {
        if ($filter === 'all') return;
        if ($filter === 'striked') $builder->whereIn('copyright_status', ['STRIKED', 'TAKEDOWN', 'CLAIMED']);
        elseif (in_array($filter, ['public', 'private', 'unlisted', 'blocked'])) $builder->where('visibility', $filter);
        else $builder->where('status', $filter);
    }

    private function getOptimizedGraphStats($id, $type) {
        $last7Days = date('Y-m-d', strtotime('-6 days'));
        $query = $this->db->table('views')->select("DATE(created_at) as date, COUNT(*) as views")
            ->where(['viewable_id' => $id, 'viewable_type' => $type])->where("created_at >=", $last7Days)
            ->groupBy("DATE(created_at)")->get()->getResultArray();
        $map = []; foreach ($query as $row) $map[$row['date']] = (int)$row['views'];
        $stats = []; for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $stats[] = ['date' => $date, 'views' => $map[$date] ?? 0];
        }
        return $stats;
    }

    private function getMaxGraphValue($id, $type) {
        $stats = $this->getOptimizedGraphStats($id, $type);
        $max = 10; foreach($stats as $s) if($s['views'] > $max) $max = $s['views'];
        return $max;
    }
}
