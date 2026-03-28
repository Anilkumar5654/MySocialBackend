<?php

namespace App\Controllers\Api\Actions;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class SocialController extends BaseController
{
    use ResponseTrait;

    /**
     * ✅ 1. TOGGLE FOLLOW (Private Account & React Native Synced)
     */
    public function toggleFollow()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        
        $json = $this->request->getJSON(true) ?: [];
        $targetUserId = $json['user_id'] ?? $this->request->getVar('user_id'); 
        
        $uri = $this->request->getUri()->getPath();
        $isUnfollowAction = str_contains($uri, 'unfollow');

        if (!$targetUserId) {
            return $this->fail('Target ID is required', 400);
        }

        if ($targetUserId == $currentUserId) {
            return $this->fail('You cannot follow yourself', 400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 🛡️ Target User ki Privacy Check karein
        $targetUser = $db->table('users')->select('is_private')->where('id', $targetUserId)->get()->getRow();
        $isTargetPrivate = ($targetUser && (int)$targetUser->is_private === 1);

        $builder = $db->table('follows');
        $existing = $builder->where([
            'follower_id'  => $currentUserId,
            'following_id' => $targetUserId
        ])->get()->getRow();

        // 🧠 Mutual Check
        $reverseFollow = $db->table('follows')->where([
            'follower_id' => $targetUserId, 
            'following_id' => $currentUserId,
            'status'       => 'accepted' // Sirf accepted hi mutual mana jayega
        ])->get()->getRow();
        
        $isFollowedByViewer = $reverseFollow ? true : false;
        $isRequested = false;
        $isFollowing = false;

        if ($isUnfollowAction) {
            if ($existing) {
                $builder->where('id', $existing->id)->delete();
                
                // Agar pehle se accepted tha tabhi count ghatao
                if ($existing->status === 'accepted') {
                    $db->table('users')->where('id', $currentUserId)->decrement('following_count');
                    $db->table('users')->where('id', $targetUserId)->decrement('followers_count');
                    $db->table('follows')->where(['follower_id' => $targetUserId, 'following_id' => $currentUserId])->update(['is_mutual' => 0]);
                }
            }
            $isFollowing = false;
        } else {
            if (!$existing) {
                // 🔒 PRIVATE ACCOUNT LOGIC
                if ($isTargetPrivate) {
                    $builder->insert([
                        'follower_id'  => $currentUserId,
                        'following_id' => $targetUserId,
                        'is_mutual'    => 0,
                        'status'       => 'pending', // Follow Request Stage
                        'created_at'   => date('Y-m-d H:i:s')
                    ]);
                    $isRequested = true;
                    $isFollowing = false;
                } else {
                    // 🔓 PUBLIC ACCOUNT LOGIC
                    $isMutual = $isFollowedByViewer ? 1 : 0;
                    $builder->insert([
                        'follower_id'  => $currentUserId,
                        'following_id' => $targetUserId,
                        'is_mutual'    => $isMutual,
                        'status'       => 'accepted',
                        'created_at'   => date('Y-m-d H:i:s')
                    ]);

                    $db->table('users')->where('id', $currentUserId)->increment('following_count');
                    $db->table('users')->where('id', $targetUserId)->increment('followers_count');

                    if ($isMutual) {
                        $db->table('follows')->where(['follower_id' => $targetUserId, 'following_id' => $currentUserId])->update(['is_mutual' => 1]);
                    }
                    $isFollowing = true;
                }

                if (isset($this->notification)) {
                    $type = $isRequested ? 'follow_request' : 'follow';
                    $this->notification->send($targetUserId, $currentUserId, $type, 'user', $targetUserId);
                }
            } else {
                // Agar status pending hai toh frontend ko batao
                $isRequested = ($existing->status === 'pending');
                $isFollowing = ($existing->status === 'accepted');
            }
        }

        $db->transComplete();

        $freshStats = $db->table('users')->select('followers_count, following_count')->where('id', $targetUserId)->get()->getRow();

        return $this->respond([
            'success'               => true, 
            'is_following'          => $isFollowing, 
            'is_requested'          => $isRequested, // 🔥 Naya flag for Requested button
            'is_followed_by_viewer' => $isFollowedByViewer,
            'can_message'           => ($isFollowing && $isFollowedByViewer), 
            'target_user_id'        => (string)$targetUserId, 
            'followers_count'       => (int)($freshStats->followers_count ?? 0),
            'following_count'       => (int)($freshStats->following_count ?? 0)
        ]);
    }

    /**
     * ✅ 2. HANDLE FOLLOW REQUEST (Accept/Reject)
     */
    public function handleFollowRequest()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?: [];
        $followerId = $json['user_id'] ?? $this->request->getVar('user_id'); // Jisne request bheji
        $action     = $json['action'] ?? $this->request->getVar('action');  // 'accept' or 'reject'

        if (!$followerId || !in_array($action, ['accept', 'reject'])) {
            return $this->fail('Invalid parameters', 400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $builder = $db->table('follows');
        $request = $builder->where([
            'follower_id'  => $followerId,
            'following_id' => $currentUserId,
            'status'       => 'pending'
        ])->get()->getRow();

        if (!$request) {
            return $this->fail('No pending request found', 404);
        }

        if ($action === 'accept') {
            // 🧠 Mutual Check
            $reverseFollow = $db->table('follows')->where([
                'follower_id'  => $currentUserId, 
                'following_id' => $followerId,
                'status'       => 'accepted'
            ])->get()->getRow();

            $isMutual = $reverseFollow ? 1 : 0;

            // Update Follow status
            $builder->where('id', $request->id)->update([
                'status'    => 'accepted',
                'is_mutual' => $isMutual
            ]);

            // Update counts
            $db->table('users')->where('id', $followerId)->increment('following_count');
            $db->table('users')->where('id', $currentUserId)->increment('followers_count');

            if ($isMutual) {
                $db->table('follows')->where('id', $reverseFollow->id)->update(['is_mutual' => 1]);
            }

            // 🔥 FIX: Mark the 'follow_request' notification as READ so count updates
            $db->table('notifications')->where([
                'user_id'  => $currentUserId,
                'actor_id' => $followerId,
                'type'     => 'follow_request'
            ])->update(['is_read' => 1]);

            if (isset($this->notification)) {
                $this->notification->send($followerId, $currentUserId, 'follow_accept', 'user', $followerId);
            }
        } else {
            // Reject: Delete the request and the notification
            $builder->where('id', $request->id)->delete();

            // 🔥 FIX: Delete notification record for rejected request
            $db->table('notifications')->where([
                'user_id'  => $currentUserId,
                'actor_id' => $followerId,
                'type'     => 'follow_request'
            ])->delete();
        }

        $db->transComplete();

        return $this->respond([
            'success' => true, 
            'message' => 'Follow request ' . ($action === 'accept' ? 'accepted' : 'rejected') . ' successfully'
        ]);
    }

    /**
     * ✅ 3. TOGGLE BLOCK (Smart Version Fixed)
     */
    public function toggleBlock()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?: [];
        $targetUserId = $json['entity_id'] ?? $this->request->getVar('entity_id'); 
        $action       = $json['action'] ?? $this->request->getVar('action');

        if (!$targetUserId || !in_array($action, ['block', 'unblock'])) {
            return $this->fail('Invalid parameters', 400);
        }

        if ($targetUserId == $currentUserId) {
            return $this->fail('Action not allowed on self', 400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        if ($action === 'block') {
            $exists = $db->table('blocks')->where(['blocker_id' => $currentUserId, 'blocked_entity_id' => $targetUserId])->countAllResults();
            
            if (!$exists) {
                $db->table('blocks')->insert([
                    'blocker_id'        => $currentUserId,
                    'blocked_entity_id' => $targetUserId,
                    'blocked_type'      => 'user',
                    'created_at'        => date('Y-m-d H:i:s')
                ]);

                $db->table('follows')->groupStart()
                    ->where(['follower_id' => $currentUserId, 'following_id' => $targetUserId])
                    ->orWhere(['follower_id' => $targetUserId, 'following_id' => $currentUserId])
                ->groupEnd()->delete();
            }
        } else {
            $db->table('blocks')->where(['blocker_id' => $currentUserId, 'blocked_entity_id' => $targetUserId])->delete();
        }

        $db->transComplete();

        return $this->respond(['success' => true, 'message' => ucfirst($action) . "ed successfully"]);
    }

    /**
     * ✅ 4. SET NOTIFICATION PREFERENCE (YouTube Style)
     */
    public function setNotificationPreference()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $json = $this->request->getJSON(true) ?: [];
        
        $targetUserId = $json['user_id'] ?? $this->request->getVar('user_id'); 
        $preference   = $json['preference'] ?? $this->request->getVar('preference'); // 'all', 'personalized', 'none'

        if (!$targetUserId || !in_array($preference, ['all', 'personalized', 'none'])) {
            return $this->fail('Invalid parameters', 400);
        }

        $db = \Config\Database::connect();
        
        $updated = $db->table('follows')->where([
            'follower_id'  => $currentUserId,
            'following_id' => $targetUserId
        ])->update(['notification_pref' => $preference]);

        if ($updated) {
            return $this->respond([
                'success'    => true, 
                'message'    => 'Notification preference updated to ' . $preference,
                'preference' => $preference
            ]);
        }

        return $this->fail('Follow record not found', 404);
    }
}
