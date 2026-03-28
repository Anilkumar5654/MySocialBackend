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
     * ✅ 1. LIST CONTENT (Video + Reels Unified)
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

        // 🔥 UPDATED: Added likes_count and comments_count from your DB structure
        $videoFields = "v.id, v.title AS title, v.views_count AS views, v.status, v.visibility, v.scheduled_at, v.thumbnail_url AS thumbnail, v.video_url, v.duration, v.monetization_enabled, v.copyright_status, v.created_at, v.likes_count, v.comments_count, 'video' AS content_type";
        $reelFields  = "r.id, r.caption AS title, r.views_count AS views, r.status, r.visibility, r.scheduled_at, r.thumbnail_url AS thumbnail, r.video_url, r.duration, r.monetization_enabled, r.copyright_status, r.created_at, r.likes_count, r.comments_count, 'reel' AS content_type"; 

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
                $fields = ($type === 'reel') ? $reelFields : $videoFields;
                // Syncing table alias for single queries
                $fields = str_replace(['v.', 'r.'], 't.', $fields);
                
                $builder = $this->db->table($table . ' AS t')->select($fields)->where('t.user_id', $userId);
                $this->applySmartFilter($builder, $filter);
                if (!empty($search)) $builder->like(($type === 'reel' ? "t.caption" : "t.title"), $search);
                $total = $builder->countAllResults(false);
                $items = $builder->orderBy('t.created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();
            }

            foreach ($items as &$item) {
                $item['thumbnail'] = get_media_url($item['thumbnail'] ?? '');
                $item['views'] = (int)$item['views'];
                $item['likes_count'] = (int)($item['likes_count'] ?? 0);
                $item['comments_count'] = (int)($item['comments_count'] ?? 0);
                $item['monetization_enabled'] = (int)($item['monetization_enabled'] ?? 0); 
                $item['strikes'] = $this->getContentStrikes($item['id'], strtoupper($item['content_type']));
            }

            return $this->respond([
                'success' => true, 
                'items' => $items, 
                'meta' => [
                    'total' => (int)$total, 
                    'hasMore' => ($total > ($offset + $limit))
                ]
            ]);
        } catch (\Exception $e) { 
            return $this->respond(['success' => false, 'error' => $e->getMessage()], 500); 
        }
    }

    /**
     * ✅ 2. GET DETAILS (100% Sync with Taggables)
     */
    public function details($id = null)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        
        $type = strtolower($this->request->getGet('type') ?? 'video');
        $table = ($type === 'reel') ? 'reels' : 'videos';
        
        $content = $this->db->table($table)->select($type === 'reel' ? "*, caption AS title" : "*")->where(['id' => $id, 'user_id' => $userId])->get()->getRowArray();
        
        if (!$content) return $this->failNotFound();

        // Sync tags with lowercase type for consistency
        $tagsData = $this->db->table('taggables t')
            ->select('h.tag')
            ->join('hashtags h', 'h.id = t.hashtag_id')
            ->where(['t.taggable_id' => $id, 't.taggable_type' => $type])
            ->get()->getResultArray();
            
        $content['tags'] = implode(', ', array_column($tagsData, 'tag'));
        $content['thumbnail'] = get_media_url($content['thumbnail_url'] ?? ''); 
        $content['video_url'] = get_media_url($content['video_url'] ?? '');
        $content['strikes'] = $this->getContentStrikes($id, strtoupper($type));

        return $this->respond(['success' => true, 'meta' => $content]);
    }

    /**
     * ✅ 3. BULLETPROOF UPDATE (With Auto-Comma Logic)
     */
    public function update($idFromUrl = null)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();

        $id = $idFromUrl ?? $this->request->getVar('id');
        $type = strtolower($this->request->getVar('type') ?? 'video');
        $table = ($type === 'reel') ? 'reels' : 'videos';

        if (!$id) return $this->fail('Missing Content ID');

        $exists = $this->db->table($table)->where(['id' => $id, 'user_id' => $userId])->get()->getRow();
        if (!$exists) return $this->failNotFound('Content not found or access denied');

        $updateData = [];
        $fields = ['visibility', 'category', 'allow_comments', 'monetization_enabled'];
        foreach($fields as $f) {
            $val = $this->request->getVar($f);
            if ($val !== null) $updateData[$f] = $val;
        }

        if ($type === 'video') {
            $updateData['title'] = $this->request->getVar('title');
            $updateData['description'] = $this->request->getVar('description');
        } else {
            $updateData['caption'] = $this->request->getVar('caption');
        }

        // Thumbnail Processing
        $file = $this->request->getFile('thumbnail');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            if (!empty($exists->thumbnail_url)) delete_media_master($exists->thumbnail_url);
            $updateData['thumbnail_url'] = upload_media_master($file, $type . '_thumbnail');
        }

        if ($this->db->table($table)->where('id', $id)->update($updateData)) {
            $rawTags = $this->request->getPost('tags') ?? $this->request->getVar('tags');

            if ($rawTags !== null) {
                $rawTags = trim($rawTags);
                if (!empty($rawTags)) {
                    $tagsArray = (strpos($rawTags, ',') !== false) 
                        ? array_map('trim', explode(',', $rawTags)) 
                        : [$rawTags];
                    
                    $tagsArray = array_filter($tagsArray);
                    $this->hashtagHelper->syncPlainTags($id, $type, $tagsArray); 
                } else {
                    $this->hashtagHelper->syncPlainTags($id, $type, []);
                }
            }
            return $this->respond(['success' => true, 'message' => 'Content updated successfully']);
        }
        return $this->failServerError('Failed to update content');
    }

    /**
     * ✅ 4. DELETE
     */
    public function delete($id = null)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        $id = $id ?? $this->request->getVar('id');
        $type = strtolower($this->request->getVar('type') ?? 'video');

        if (!$id) return $this->fail('Content ID is required');

        $table = ($type === 'reel') ? 'reels' : 'videos';
        $content = $this->db->table($table)->where(['id' => $id, 'user_id' => $userId])->get()->getRow();

        if ($content) {
            if(!empty($content->video_url)) delete_media_master($content->video_url);
            if(!empty($content->thumbnail_url)) delete_media_master($content->thumbnail_url);
            
            $this->db->table($table)->where('id', $id)->delete();
            $this->db->table('taggables')->where(['taggable_id' => $id, 'taggable_type' => $type])->delete();
            
            return $this->respond(['success' => true, 'message' => 'Deleted successfully']);
        }

        return $this->failNotFound('Content not found or unauthorized');
    }

    /**
     * ✅ 5. GET CONTENT STRIKES
     */
    private function getContentStrikes($id, $type) {
        $strikes = $this->db->table('channel_strikes s')
            ->select('s.*, v.title as orig_v_title, v.thumbnail_url as orig_v_thumb, cv.name as v_owner, r.caption as orig_r_title, r.thumbnail_url as orig_r_thumb, cr.name as r_owner')
            ->join('videos v', 'v.id = s.original_content_id', 'left')
            ->join('channels cv', 'cv.id = v.channel_id', 'left')
            ->join('reels r', 'r.id = s.original_content_id', 'left')
            ->join('channels cr', 'cr.id = r.channel_id', 'left')
            ->where(['s.content_type' => strtoupper($type), 's.content_id' => $id, 's.status' => 'ACTIVE'])
            ->get()->getResultArray();

        foreach ($strikes as &$s) {
            if (!empty($s['orig_v_title'])) {
                $s['original_title']     = $s['orig_v_title'];
                $s['original_thumbnail'] = get_media_url($s['orig_v_thumb'] ?? '');
                $s['original_owner']     = $s['v_owner'];
                $s['original_type']      = 'VIDEO';
            } elseif (!empty($s['orig_r_title'])) {
                $s['original_title']     = $s['orig_r_title'];
                $s['original_thumbnail'] = get_media_url($s['orig_r_thumb'] ?? '');
                $s['original_owner']     = $s['r_owner'];
                $s['original_type']      = 'REEL';
            } else {
                $s['original_title']     = 'External/Manual Strike';
                $s['original_thumbnail'] = '';
                $s['original_owner']     = 'System';
                $s['original_type']      = 'UNKNOWN';
            }

            unset($s['orig_v_title'], $s['orig_v_thumb'], $s['v_owner'], $s['orig_r_title'], $s['orig_r_thumb'], $s['r_owner']);
        }

        return $strikes;
    }

    /**
     * ✅ 6. APPLY SMART FILTER
     */
    private function applySmartFilter(&$builder, $filter) {
        if ($filter === 'all') return;
        if ($filter === 'striked') {
            $builder->whereIn('copyright_status', ['STRIKED', 'TAKEDOWN', 'CLAIMED']);
        } elseif (in_array($filter, ['public', 'private', 'unlisted', 'blocked'])) {
            $builder->where('visibility', $filter);
        } else {
            $builder->where('status', $filter);
        }
    }
}
