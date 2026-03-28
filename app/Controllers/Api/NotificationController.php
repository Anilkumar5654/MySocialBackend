<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class NotificationController extends BaseController
{
    use ResponseTrait;

    public function __construct()
    {
        // Media helper load karna jaruri hai URLs generate karne ke liye
        helper(['media']);
    }

    /**
     * ✅ 1. FETCH ALL NOTIFICATIONS
     * Includes Video, Reel, Post, Follow, Mention etc.
     */
    public function index()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized('User-ID missing');

        $limit  = $this->request->getVar('limit') ?? 20;
        $offset = $this->request->getVar('offset') ?? 0;

        $builder = $this->db->table('notifications n');
        
        // Basic fields + Actor details
        $builder->select('n.*, u.username as actor_name, u.avatar as actor_avatar');
        
        // 🔥 Follow Status Subqueries for Buttons
        // Check 1: Kya maine is actor ko follow kiya hai?
        $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = {$currentUserId} AND following_id = n.actor_id) as is_following");

        // Check 2: Kya is actor ne mujhe follow kiya hai?
        $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = n.actor_id AND following_id = {$currentUserId}) as is_followed_by_viewer");

        $builder->join('users u', 'u.id = n.actor_id', 'left');
        
        // User specific notifications
        $builder->where('n.user_id', $currentUserId);

        // 🔥 NO FILTERS: Video aur Reel ab allowed hain

        $builder->orderBy('n.created_at', 'DESC');
        $builder->limit($limit, $offset);

        $rawNotifications = $builder->get()->getResultArray();
        $formattedNotifications = [];

        foreach ($rawNotifications as $row) {
            
            // Metadata parsing (Thumbnails/Comments)
            $metadata = null;
            if (!empty($row['metadata'])) {
                $metadata = json_decode($row['metadata'], true);
                if (isset($metadata['thumbnail'])) {
                    $metadata['thumbnail'] = get_media_url($metadata['thumbnail']);
                }
            }

            $formattedNotifications[] = [
                'id'            => (string)$row['id'],
                'type'          => $row['type'],
                'notifiable_id' => (string)$row['notifiable_id'],
                'entity_type'   => $row['notifiable_type'],
                'message'       => $row['message'],
                'is_read'       => (bool)$row['is_read'],
                'created_at'    => $row['created_at'],
                
                // Status for Follow/Follow-back buttons
                'is_following'          => (bool)$row['is_following'],
                'is_followed_by_viewer' => (bool)$row['is_followed_by_viewer'],
                
                'user' => [
                    'id'       => (string)$row['actor_id'],
                    'username' => $row['actor_name'] ?? 'Unknown User',
                    'avatar'   => get_media_url($row['actor_avatar'])
                ],

                'metadata' => $metadata
            ];
        }

        return $this->respond([
            'success'       => true,
            'notifications' => $formattedNotifications,
            'unread_count'  => $this->getUnreadCount($currentUserId)
        ]);
    }

    /**
     * ✅ 2. MARK READ
     * Mark single or all notifications as read.
     */
    public function markRead($id = null)
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized('User-ID missing');
        
        $builder = $this->db->table('notifications');

        if ($id === 'all') {
            // Mark all read for this user (All types included)
            $builder->where('user_id', $currentUserId)->update(['is_read' => 1]);
        } else {
            // Single ID read
            $builder->where(['id' => $id, 'user_id' => $currentUserId])->update(['is_read' => 1]);
        }

        return $this->respond(['success' => true, 'message' => 'Marked as read']);
    }

    /**
     * ✅ 3. GET UNREAD COUNT (Total)
     * Counts all unread notifications without filters.
     */
    private function getUnreadCount($userId)
    {
        return $this->db->table('notifications')
                        ->where(['user_id' => $userId, 'is_read' => 0])
                        ->countAllResults();
    }
    
    /**
     * Public endpoint for Unread Count
     */
    public function unreadCount()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized('User-ID missing');

        return $this->respond([
            'success' => true,
            'count'   => $this->getUnreadCount($currentUserId)
        ]);
    }

    /**
     * ✅ 4. GET PENDING FOLLOW REQUESTS
     * Fetch users who sent follow requests but are still 'pending'
     */
    public function getFollowRequests()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized('User-ID missing');

        // 🔥 Logic: 'follows' table se wo users uthao jinhone is user ko follow kiya hai
        // status strictly 'pending' hona chahiye
        $builder = $this->db->table('follows f');
        $builder->select('u.id, u.username, u.name, u.avatar');
        $builder->join('users u', 'u.id = f.follower_id');
        $builder->where('f.following_id', $currentUserId);
        $builder->where('f.status', 'pending');
        $builder->orderBy('f.created_at', 'DESC');

        $requests = $builder->get()->getResultArray();

        // Avatar URLs generate karna
        foreach ($requests as &$req) {
            $req['id']     = (string)$req['id'];
            $req['avatar'] = get_media_url($req['avatar']);
        }

        return $this->respond([
            'success'  => true,
            'requests' => $requests
        ]);
    }
}
