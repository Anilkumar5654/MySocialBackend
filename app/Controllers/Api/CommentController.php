<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\CommentModel;
use App\Helpers\InteractionHelper; 

class CommentController extends BaseController
{
    use ResponseTrait;

    protected $commentModel;
    protected $db;
    protected $interaction;

    public function __construct()
    {
        $this->commentModel = new CommentModel();
        $this->db = \Config\Database::connect();
        helper(['url', 'text']);
        
        // ✅ Interaction Helper Initialized
        $this->interaction = new InteractionHelper();
    }

    /**
     * ✅ 1. ADD COMMENT (Integrated with Notifications & Viral Score)
     */
    public function add()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized('User-ID missing');

        $json = $this->request->getJSON(true) ?? $this->request->getPost();
        if (empty($json['content'])) return $this->fail('Content empty');

        // ID & Type detection
        $type = $json['type'] ?? 'post';
        $id = $json['post_id'] ?? ($json['id'] ?? ($json['content_id'] ?? ($json['reel_id'] ?? ($json['video_id'] ?? null))));

        // Enum Fix
        $type = preg_replace('/s$/', '', strtolower(trim($type)));
        if (in_array($type, ['photo', 'image', 'images', 'text', 'short'])) {
            $type = 'post';
        }

        if (!$id) return $this->fail("Content ID missing.");

        $parentId = (!empty($json['parent_id']) && $json['parent_id'] != 0) ? $json['parent_id'] : null;

        $data = [
            'user_id'          => $currentUserId,
            'commentable_type' => $type, 
            'commentable_id'   => $id,
            'content'          => trim($json['content']),
            'parent_id'        => $parentId,
            'created_at'       => date('Y-m-d H:i:s')
        ];

        try {
            $this->db->transStart();
            
            // 1. Insert Comment
            if (!$this->db->table('comments')->insert($data)) {
                $error = $this->db->error();
                return $this->fail('DB Error: ' . $error['message']);
            }
            
            $insertID = $this->db->insertID();
            
            // 2. Interaction Logic (🔥 FIXED: Restriction removed for Viral Strategy)
            // Humne 'if' condition hata di hai. Ab 'post' bhi helper ke paas jayega.
            // Helper (InteractionHelper) khud check karega: 
            // - Agar video/reel hai -> Points + Viral Score.
            // - Agar post hai -> Sirf Viral Score (0 Points).
            $ownerId = $this->getContentOwnerId($type, $id);
            if ($ownerId && $ownerId != $currentUserId) {
                $this->interaction->handleInteraction($ownerId, $currentUserId, $id, $type, 'comment');
            }

            // 3. UNIVERSAL COUNTER UPDATE
            $this->updateCounter($type, $id, '+');
            
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->fail('Transaction failed.');
            }

            // 🔔 NOTIFICATION MAGIC
            $contentData = $this->getContentData($type, $id);
            if ($contentData && $contentData->user_id != $currentUserId) {
                $metadata = [
                    'thumbnail' => $contentData->thumb,
                    'comment_preview' => substr($data['content'], 0, 60)
                ];

                $this->notification->send(
                    $contentData->user_id, 
                    $currentUserId, 
                    'comment', 
                    $type, 
                    $id, 
                    $metadata
                );
            }

            return $this->respondCreated(['success' => true, 'comment_id' => (string)$insertID]);

        } catch (\Exception $e) {
            return $this->fail('Exception: ' . $e->getMessage());
        }
    }

    // ... [Baki list aur delete functions same rahenge] ...

    public function list()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $type = $this->request->getGet('type') ?? $this->request->getVar('type');
        $id   = $this->request->getGet('id') ?? $this->request->getGet('post_id') ?? $this->request->getVar('id');

        if ($type) {
            $type = preg_replace('/s$/', '', strtolower(trim($type)));
            if (in_array($type, ['photo', 'image', 'text', 'short'])) $type = 'post';
        }

        if (!$type || !$id) return $this->fail('ID and Type required', 400);

        $builder = $this->db->table('comments c');
        $builder->select('c.*, u.username, u.name, u.avatar, u.is_verified');
        $builder->join('users u', 'u.id = c.user_id');
        $builder->where(['c.commentable_type' => $type, 'c.commentable_id' => $id]);
        $builder->orderBy('c.created_at', 'ASC'); 
        
        $rawComments = $builder->get()->getResultArray();

        $mainComments = [];
        $replies = [];

        foreach ($rawComments as $row) {
            $avatar = $row['avatar'];
            if (!empty($avatar) && !str_starts_with($avatar, 'http')) {
                $avatar = base_url('uploads/' . $avatar);
            }

            $formatted = [
                'id'          => (string)$row['id'],
                'user_id'     => (string)$row['user_id'],
                'content'     => $row['content'],
                'parent_id'   => $row['parent_id'],
                'created_at'  => $row['created_at'],
                'is_owner'    => (string)$row['user_id'] === (string)$currentUserId,
                'user' => [
                    'id'          => (string)$row['user_id'],
                    'username'    => $row['username'],
                    'name'        => $row['name'] ?? $row['username'],
                    'avatar'      => empty($avatar) ? null : $avatar, 
                    'is_verified' => (bool)$row['is_verified']
                ],
                'replies' => [] 
            ];

            if (empty($row['parent_id'])) {
                $mainComments[$row['id']] = $formatted;
            } else {
                $replies[] = $formatted;
            }
        }

        foreach ($replies as $reply) {
            if (isset($mainComments[$reply['parent_id']])) {
                $mainComments[$reply['parent_id']]['replies'][] = $reply;
            }
        }

        return $this->respond(['success' => true, 'comments' => array_values($mainComments)]);
    }

    public function delete()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $commentId = $this->request->getGet('comment_id') ?? $this->request->getPost('comment_id');

        $comment = $this->commentModel->find($commentId);
        if (!$comment) return $this->failNotFound('Comment not found');

        if ((string)$comment['user_id'] !== (string)$currentUserId) {
             return $this->failForbidden('You can only delete your own comments');
        }

        try {
            $this->db->transStart();
            $this->commentModel->delete($commentId);
            $this->updateCounter($comment['commentable_type'], $comment['commentable_id'], '-');
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->fail('Transaction failed during delete.');
            }

            return $this->respond(['success' => true, 'message' => 'Deleted successfully']);

        } catch (\Exception $e) {
            return $this->fail('Exception: ' . $e->getMessage());
        }
    }

    private function getContentData($type, $id)
    {
        if ($type === 'post') {
            $post = $this->db->table('posts')->select('user_id')->where('id', $id)->get()->getRow();
            if (!$post) return null;

            $media = $this->db->table('post_media')
                ->select('thumbnail_url, media_url')
                ->where('post_id', $id)
                ->orderBy('display_order', 'ASC')
                ->get()
                ->getRow();

            return (object)[
                'user_id' => $post->user_id,
                'thumb'   => $media->thumbnail_url ?? $media->media_url ?? null
            ];
        }

        $table = match($type) { 'reel' => 'reels', 'video' => 'videos', default => null };
        if (!$table) return null;

        return $this->db->table($table)
            ->select('user_id, thumbnail_url as thumb')
            ->where('id', $id)
            ->get()
            ->getRow();
    }

    private function getContentOwnerId($type, $id)
    {
        $table = match($type) { 'post' => 'posts', 'reel' => 'reels', 'video' => 'videos', default => null };
        if (!$table) return null;
        $row = $this->db->table($table)->select('user_id')->where('id', $id)->get()->getRow();
        return $row ? $row->user_id : null;
    }

    private function updateCounter($type, $id, $operator)
    {
        $table = match($type) { 'post' => 'posts', 'video' => 'videos', 'reel' => 'reels', default => null };
        if ($table && $id) {
            $this->db->query("UPDATE `$table` SET comments_count = GREATEST(0, comments_count $operator 1) WHERE id = ?", [$id]);
        }
    }
}
