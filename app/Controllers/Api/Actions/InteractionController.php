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
        $this->interaction = new InteractionHelper();
        helper(['url', 'text']);
    }

    /**
     * 🔥 BULLET-PROOF TRACK IMPRESSIONS
     * Fix: Array to String conversion fix (handles single ID or Array)
     * Fix: Batch processing with 1-Minute Anti-Spam
     */
    public function trackImpressions()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: null;
        $json = $this->request->getJSON(true);

        $type = $json['type'] ?? null; 
        $ids  = $json['ids'] ?? $json['id'] ?? [];    
        
        $trafficSource = $json['traffic_source'] ?? 'home_feed';
        $deviceId = $json['device_id'] ?? $this->request->getHeaderLine('Device-ID') ?? null;
        $ipAddress = $this->request->getIPAddress();
        $now = date('Y-m-d H:i:s');
        $antiSpamWindow = date('Y-m-d H:i:s', strtotime('-1 minute'));

        if (!$type || empty($ids)) {
            return $this->fail('Invalid impression data', 400);
        }

        // 🔥 FIX: Agar single ID aayi hai toh usey array mein badal do
        $validIds = is_array($ids) ? array_unique(array_map('intval', $ids)) : [(int)$ids];

        $table = match($type) {
            'post'  => 'posts',
            'reel'  => 'reels',
            'video' => 'videos',
            default => null
        };

        if (!$table) return $this->fail('Invalid content type', 400);

        try {
            $insertData = [];
            $trackedCount = 0;

            $this->db->transStart();

            foreach ($validIds as $id) {
                if (!$id) continue;

                // 🛑 STAGE 1: Anti-Spam Check (1 Minute Window)
                $spamCheck = $this->db->table('impressions')
                    ->where([
                        'impressionable_id'   => $id,
                        'impressionable_type' => $type
                    ])
                    ->groupStart()
                        ->where('ip_address', $ipAddress)
                        ->orWhere('device_id', $deviceId)
                    ->groupEnd()
                    ->where('created_at >=', $antiSpamWindow)
                    ->countAllResults();

                if ($spamCheck === 0) {
                    $content = $this->db->table($table)->select('user_id')->where('id', $id)->get()->getRow();
                    if (!$content) continue;

                    $insertData[] = [
                        'user_id'             => $currentUserId,
                        'creator_id'          => $content->user_id,
                        'impressionable_type' => $type,
                        'impressionable_id'   => $id,
                        'traffic_source'      => $trafficSource,
                        'ip_address'          => $ipAddress,
                        'device_id'           => $deviceId,
                        'created_at'          => $now
                    ];

                    // 📈 STAGE 2: Table Counter Update
                    $this->db->table($table)
                        ->where('id', $id)
                        ->set('impressions_count', 'impressions_count + 1', false)
                        ->update();
                    
                    $trackedCount++;
                }
            }

            // 📥 STAGE 3: Batch Insert
            if (!empty($insertData)) {
                $this->db->table('impressions')->insertBatch($insertData);
            }

            $this->db->transComplete();

            return $this->respond([
                'success' => true,
                'tracked' => $trackedCount,
                'message' => $trackedCount . ' new unique impressions tracked via ' . $trafficSource
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
        if (!in_array($type, ['post', 'reel', 'video', 'comment'])) return $this->fail('Invalid content type');

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
                // 🔥 FIX: Comment Like ke liye alag type trigger karo
                $triggerType = ($type === 'comment') ? 'comment_like' : 'like';
                $this->interaction->handleInteraction($content->user_id, $currentUserId, $id, $type, $triggerType);
            }
        }

        return $this->respond([
            'success' => true, 
            'is_liked' => $isActive, 
            'count' => $this->getCount($type, $id, 'likes_count')
        ]);
    }

    /**
     * 🟢 GET LIKES LIST LOGIC
     */
    public function getLikesList()
    {
        $itemId = $this->request->getGet('id');
        $itemType = $this->request->getGet('type'); 
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;

        if (!$itemId || !$itemType) {
            return $this->fail('ID and Type are required', 400);
        }

        $builder = $this->db->table('likes l');
        $builder->select('u.id, u.name, u.username, u.avatar, u.is_verified');
        $builder->join('users u', 'l.user_id = u.id');
        $builder->where('l.likeable_id', $itemId);
        $builder->where('l.likeable_type', $itemType);
        $builder->orderBy('l.created_at', 'DESC');

        if ($currentUserId > 0) {
            $escapedUserId = $this->db->escape($currentUserId);
            $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = {$escapedUserId} AND following_id = u.id) as is_following");
            $builder->select("(SELECT COUNT(*) FROM follows WHERE following_id = {$escapedUserId} AND follower_id = u.id) as is_followed_by_viewer");
        }

        $users = $builder->get()->getResultArray();

        foreach ($users as &$user) {
            if (function_exists('get_media_url')) {
                $user['avatar'] = get_media_url($user['avatar']);
            }
            $user['is_verified'] = (bool)$user['is_verified'];
            if (isset($user['is_following'])) {
                $user['is_following'] = (bool)$user['is_following'];
                $user['is_followed_by_viewer'] = (bool)$user['is_followed_by_viewer'];
            }
        }

        $table = match($itemType) {
            'post'  => 'posts',
            'reel'  => 'reels',
            'video' => 'videos',
            'comment' => 'comments',
            default => null
        };

        $likesCount = 0;
        $viewsCount = 0;

        if ($table) {
            $selectCols = 'likes_count';
            if ($itemType === 'reel' || $itemType === 'video') {
                $selectCols .= ', views_count';
            }
            $entity = $this->db->table($table)->select($selectCols)->where('id', $itemId)->get()->getRow();
            if ($entity) {
                $likesCount = (int)($entity->likes_count ?? 0);
                if (isset($entity->views_count)) $viewsCount = (int)$entity->views_count;
            }
        }

        return $this->respond([
            'success' => true, 
            'users' => $users,
            'likes_count' => $likesCount,
            'views_count' => $viewsCount  
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
     * 🟣 DISLIKE LOGIC
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
     * 👁️ FEEDBACK LOGIC (Interested / Not Interested / Hide Creator)
     * ✨ ADDED: Handles inserting feedback into user_content_feedback table
     */
    public function feedback()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?? [];

        $id = $json['id'] ?? null;
        $type = $json['type'] ?? null;
        $feedbackType = $json['feedback_type'] ?? 'Not Interested';

        if (!$currentUserId || !$id || !$type) {
            return $this->fail('Missing required parameters');
        }

        // Database unique index check
        $builder = $this->db->table('user_content_feedback');
        $exists = $builder->where([
            'user_id'       => $currentUserId,
            'content_type'  => $type,
            'content_id'    => $id,
            'feedback_type' => $feedbackType
        ])->get()->getRow();

        if (!$exists) {
            $builder->insert([
                'user_id'       => $currentUserId,
                'content_type'  => $type,
                'content_id'    => $id,
                'feedback_type' => $feedbackType,
                'created_at'    => date('Y-m-d H:i:s')
            ]);
        }

        return $this->respond([
            'success' => true, 
            'message' => 'Feedback saved successfully'
        ]);
    }

    private function getContentData($type, $id)
    {
        if ($type === 'post') {
            $post = $this->db->table('posts')->select('user_id')->where('id', $id)->get()->getRow();
            return $post ? (object)['user_id' => $post->user_id] : null;
        }
        if ($type === 'comment') {
            $comment = $this->db->table('comments')->select('user_id')->where('id', $id)->get()->getRow();
            return $comment ? (object)['user_id' => $comment->user_id] : null;
        }
        $table = match($type) { 'reel' => 'reels', 'video' => 'videos', default => null };
        return $table ? $this->db->table($table)->select('user_id')->where('id', $id)->get()->getRow() : null;
    }

    private function detectParams($json)
    {
        $type = $json['type'] ?? null;
        $id   = $json['id'] ?? null;
        if (!$id) {
            if (isset($json['post_id'])) { $type = 'post'; $id = $json['post_id']; }
            elseif (isset($json['reel_id'])) { $type = 'reel'; $id = $json['reel_id']; }
            elseif (isset($json['video_id'])) { $type = 'video'; $id = $json['video_id']; }
            elseif (isset($json['comment_id'])) { $type = 'comment'; $id = $json['comment_id']; }
        }
        return ['type' => $type ?? 'post', 'id' => $id];
    }

    private function updateCounter($type, $id, $column, $operator)
    {
        $table = match($type) { 'post' => 'posts', 'reel' => 'reels', 'video' => 'videos', 'comment' => 'comments', default => null };
        if ($table) $this->db->query("UPDATE `$table` SET $column = GREATEST(0, $column $operator 1) WHERE id = ?", [$id]);
    }

    private function getCount($type, $id, $column)
    {
        $table = match($type) { 'post' => 'posts', 'reel' => 'reels', 'video' => 'videos', 'comment' => 'comments', default => null };
        if (!$table) return 0;
        $row = $this->db->table($table)->select($column)->where('id', $id)->get()->getRow();
        return (int)($row->$column ?? 0);
    }
}
