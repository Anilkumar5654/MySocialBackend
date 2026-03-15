<?php

namespace App\Controllers\Api\Actions;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\InteractionHelper;

class InteractionController extends BaseController
{
    use ResponseTrait;

    protected $db;
    protected $interaction;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // ✅ Interaction Helper Initialize (Monetization & Viral Score ke liye)
        $this->interaction = new InteractionHelper();
        
        helper(['url', 'text']);
    }

    /**
     * 🔥 NEW: TRACK IMPRESSIONS (BATCH PROCESSING)
     * React Native se aane wali batch IDs ko handle karta hai.
     */
    public function trackImpressions()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true);

        $type = $json['type'] ?? null; // 'post', 'reel', or 'video'
        $ids  = $json['ids'] ?? [];    // Array: [101, 102, 105]

        if (!$type || empty($ids)) {
            return $this->fail('Invalid impression data', 400);
        }

        // Table map karna aapke DB structure ke hisaab se
        $table = match($type) {
            'post'  => 'posts',
            'reel'  => 'reels',
            'video' => 'videos',
            default => null
        };

        if (!$table) return $this->fail('Invalid content type', 400);

        try {
            $validIds = array_map('intval', array_unique($ids));

            $this->db->transStart();
            // Batch update: impressions_count ko +1 karna
            $this->db->table($table)
                ->whereIn('id', $validIds)
                ->set('impressions_count', 'impressions_count + 1', false)
                ->update();
            $this->db->transComplete();

            return $this->respond([
                'success' => true,
                'message' => count($validIds) . ' impressions tracked'
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 🟢 LIKE TOGGLE LOGIC
     */
    public function toggleLike()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?? [];
        $params = $this->detectParams($json);
        $type = $params['type']; 
        $id = $params['id'];

        if (!$id) return $this->fail('Target ID is required');

        $builder = $this->db->table('likes');
        $exists = $builder->where([
            'user_id' => $currentUserId, 
            'likeable_type' => $type, 
            'likeable_id' => $id
        ])->get()->getRow();

        $isActive = false;

        $this->db->transStart();
        if ($exists) {
            $builder->where('id', $exists->id)->delete();
            $this->updateCounter($type, $id, 'likes_count', '-');
            $isActive = false;
        } else {
            $builder->insert([
                'user_id' => $currentUserId, 
                'likeable_type' => $type, 
                'likeable_id' => $id, 
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->updateCounter($type, $id, 'likes_count', '+');
            $isActive = true;
        }
        $this->db->transComplete();

        if ($isActive) {
            $content = $this->getContentData($type, $id);
            if ($content && $content->user_id != $currentUserId) {
                // Earnings & Viral Score update via Helper
                $this->interaction->handleInteraction($content->user_id, $currentUserId, $id, $type, 'like');
            }
        }

        return $this->respond([
            'success' => true, 
            'is_liked' => $isActive, 
            'count' => $this->getCount($type, $id, 'likes_count')
        ]);
    }

    /**
     * 🔵 SHARE LOGIC
     */
    public function share()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?? [];
        $params = $this->detectParams($json);
        $type = $params['type']; 
        $id = $params['id'];

        if (!$id) return $this->fail('Target ID is required');

        try {
            $this->db->transStart();
            $this->db->table('shares')->insert([
                'user_id' => $currentUserId,
                'shareable_type' => $type,
                'shareable_id' => $id,
                'platform' => $json['platform'] ?? 'internal',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->updateCounter($type, $id, 'shares_count', '+');
            $this->db->transComplete();

            $content = $this->getContentData($type, $id);
            if ($content && $content->user_id != $currentUserId) {
                $this->interaction->handleInteraction($content->user_id, $currentUserId, $id, $type, 'share');
            }

            return $this->respond([
                'success' => true, 
                'shares_count' => $this->getCount($type, $id, 'shares_count')
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 🟡 SAVE/BOOKMARK LOGIC
     */
    public function toggleSave()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?? [];
        $params = $this->detectParams($json);
        $type = $params['type']; 
        $id = $params['id'];

        if (!$id) return $this->fail('Target ID is required');

        $builder = $this->db->table('saves');
        $exists = $builder->where([
            'user_id' => $currentUserId, 
            'saveable_type' => $type, 
            'saveable_id' => $id
        ])->get()->getRow();

        $isSaved = false;
        if ($exists) {
            $builder->where('id', $exists->id)->delete();
            $isSaved = false;
        } else {
            $builder->insert([
                'user_id' => $currentUserId, 
                'saveable_type' => $type, 
                'saveable_id' => $id, 
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $isSaved = true;
        }
        
        return $this->respond(['success' => true, 'is_saved' => $isSaved]);
    }

    /**
     * 🔴 REPORT CONTENT
     */
    public function report()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?? [];
        $params = $this->detectParams($json);
        if (!$params['id']) return $this->fail('Target ID is required');

        $this->db->table('reports')->insert([
            'reporter_id'     => $currentUserId,
            'reportable_type' => $params['type'],
            'reportable_id'   => $params['id'],
            'reason'          => $json['reason'] ?? 'Other',
            'description'     => $json['description'] ?? '',
            'created_at'      => date('Y-m-d H:i:s')
        ]);

        return $this->respond(['success' => true, 'message' => 'Report submitted']);
    }

    /**
     * 🟣 DISLIKE LOGIC (Videos Only)
     */
    public function toggleDislike()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?? [];
        $id = $json['video_id'] ?? $json['id'];
        if (!$id) return $this->fail('Video ID is required');

        $builder = $this->db->table('video_dislikes');
        $exists = $builder->where(['user_id' => $currentUserId, 'video_id' => $id])->get()->getRow();

        $this->db->transStart();
        $isDisliked = false;
        if ($exists) {
            $builder->where('id', $exists->id)->delete();
            $this->updateCounter('video', $id, 'dislikes_count', '-');
            $isDisliked = false;
        } else {
            $builder->insert(['user_id' => $currentUserId, 'video_id' => $id, 'created_at' => date('Y-m-d H:i:s')]);
            $this->updateCounter('video', $id, 'dislikes_count', '+');
            $isDisliked = true;
        }
        $this->db->transComplete();

        return $this->respond(['success' => true, 'is_disliked' => $isDisliked]);
    }

    /**
     * 🛠 INTERNAL HELPERS
     */

    private function getContentData($type, $id)
    {
        if ($type === 'post') {
            $post = $this->db->table('posts')->select('user_id')->where('id', $id)->get()->getRow();
            if (!$post) return null;
            return (object)['user_id' => $post->user_id];
        }

        $table = match($type) {
            'reel'  => 'reels',
            'video' => 'videos',
            default => null
        };
        if (!$table) return null;

        return $this->db->table($table)->select('user_id')->where('id', $id)->get()->getRow();
    }

    private function detectParams($json)
    {
        $type = $json['type'] ?? null;
        $id   = $json['id'] ?? null;
        if (!$id) {
            if (isset($json['post_id']))            { $type = 'post';    $id = $json['post_id']; }
            elseif (isset($json['reel_id']))        { $type = 'reel';    $id = $json['reel_id']; }
            elseif (isset($json['video_id']))       { $type = 'video';   $id = $json['video_id']; }
            elseif (isset($json['comment_id']))     { $type = 'comment'; $id = $json['comment_id']; }
        }
        return ['type' => $type ?? 'post', 'id' => $id];
    }

    private function updateCounter($type, $id, $column, $operator)
    {
        $table = match($type) {
            'post'    => 'posts',
            'reel'    => 'reels',
            'video'   => 'videos',
            'comment' => 'comments',
            default   => null
        };
        if ($table) {
            $this->db->query("UPDATE `$table` SET $column = GREATEST(0, $column $operator 1) WHERE id = ?", [$id]);
        }
    }

    private function getCount($type, $id, $column)
    {
        $table = match($type) {
            'post'    => 'posts',
            'reel'    => 'reels',
            'video'   => 'videos',
            'comment' => 'comments',
            default   => null
        };
        if (!$table) return 0;
        $row = $this->db->table($table)->select($column)->where('id', $id)->get()->getRow();
        return (int)($row->$column ?? 0);
    }
}
