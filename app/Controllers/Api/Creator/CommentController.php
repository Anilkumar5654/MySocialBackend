<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Throwable;

class CommentController extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['media', 'url', 'text']);
    }

    /**
     * ✅ 1. FETCH ALL COMMENTS FOR CREATOR'S CONTENT
     */
    public function index()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized('User-ID missing');

        $limit  = $this->request->getVar('limit') ?? 20;
        $offset = $this->request->getVar('offset') ?? 0;

        $escapedUserId = $this->db->escape($currentUserId);

        $builder = $this->db->table('comments c');
        $builder->select('
            c.id as comment_id, 
            c.content, 
            c.commentable_type, 
            c.commentable_id, 
            c.parent_id,
            c.created_at,
            c.likes_count,
            (SELECT COUNT(*) FROM likes WHERE likeable_type = "comment" AND likeable_id = c.id AND user_id = ' . $escapedUserId . ') as is_liked,
            u.username as sender_name, 
            u.avatar as sender_avatar,
            COALESCE(v.title, r.caption) as content_title,
            COALESCE(v.thumbnail_url, r.thumbnail_url) as content_thumbnail
        ');
        
        $builder->join('users u', 'u.id = c.user_id');
        $builder->join('videos v', 'v.id = c.commentable_id AND c.commentable_type = "video"', 'left');
        $builder->join('reels r', 'r.id = c.commentable_id AND c.commentable_type = "reel"', 'left');

        $builder->groupStart()
                ->where('v.user_id', $currentUserId)
                ->orWhere('r.user_id', $currentUserId)
                ->groupEnd();

        $builder->orderBy('c.created_at', 'DESC');
        $builder->limit($limit, $offset);

        $comments = $builder->get()->getResultArray();

        foreach ($comments as &$row) {
            $row['comment_id'] = (string)$row['comment_id'];
            $row['parent_id'] = $row['parent_id'] ? (string)$row['parent_id'] : null; // 🔥 Required for Frontend Nesting
            $row['likes_count'] = (int)$row['likes_count'];
            $row['is_liked'] = (bool)$row['is_liked'];
            $row['sender_avatar'] = get_media_url($row['sender_avatar'], 'avatar');
            $row['content_thumbnail'] = get_media_url($row['content_thumbnail'], 'video_thumb');
        }

        return $this->respond(['success' => true, 'comments' => $comments]);
    }

    /**
     * ✅ 2. DELETE COMMENT (Creator Power)
     * Rule: Creator can delete any comment on their content. Also removes associated replies.
     */
    public function delete($id = null)
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        // 🔥 Accept ID from URL or Post/JSON
        $commentId = $id ?? $this->request->getVar('comment_id');

        if (!$commentId) return $this->fail('Comment ID required');

        $comment = $this->db->table('comments')->where('id', $commentId)->get()->getRow();
        if (!$comment) return $this->failNotFound('Comment not found');

        // Check ownership of content (Video or Reel)
        $table = ($comment->commentable_type === 'video') ? 'videos' : 'reels';
        $content = $this->db->table($table)->where('id', $comment->commentable_id)->get()->getRow();

        // Safety Logic: Content Owner OR Comment Author can delete
        $isContentOwner = ($content && (string)$content->user_id === (string)$currentUserId);
        $isCommenter = ((string)$comment->user_id === (string)$currentUserId);

        if (!$isContentOwner && !$isCommenter) {
            return $this->failForbidden('You do not have permission to delete this comment.');
        }

        try {
            $this->db->transStart();
            
            // 🔥 Count total comments to be deleted (Main comment + Its replies)
            $commentsToDelete = $this->db->table('comments')
                                         ->where('id', $commentId)
                                         ->orWhere('parent_id', $commentId)
                                         ->countAllResults();

            if ($commentsToDelete > 0) {
                // 1. Delete the comment AND its replies safely
                $this->db->table('comments')
                         ->groupStart()
                         ->where('id', $commentId)
                         ->orWhere('parent_id', $commentId)
                         ->groupEnd()
                         ->delete();
                
                // 2. Sync Counter accurately (GREATEST usage to avoid negative numbers)
                $this->db->query("UPDATE `$table` SET comments_count = GREATEST(0, comments_count - ?) WHERE id = ?", [$commentsToDelete, $comment->commentable_id]);
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                return $this->failServerError('Transaction failed.');
            }

            return $this->respond(['success' => true, 'message' => 'Comment and its replies deleted successfully']);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * ✅ 3. ADD REPLY FROM DASHBOARD
     */
    public function reply()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true);

        if (empty($json['content']) || empty($json['parent_id'])) {
            return $this->fail('Incomplete data');
        }

        $data = [
            'user_id'          => $currentUserId,
            'commentable_type' => $json['type'] ?? 'video',
            'commentable_id'   => $json['id'],
            'content'          => trim($json['content']),
            'parent_id'        => $json['parent_id'],
            'created_at'       => date('Y-m-d H:i:s')
        ];

        if ($this->db->table('comments')->insert($data)) {
            // Update counter for reply as well
            $table = ($data['commentable_type'] === 'video') ? 'videos' : 'reels';
            $this->db->query("UPDATE `$table` SET comments_count = comments_count + 1 WHERE id = ?", [$data['commentable_id']]);
            
            return $this->respondCreated(['success' => true, 'message' => 'Reply posted']);
        }
        return $this->fail('Failed to post reply');
    }
}
