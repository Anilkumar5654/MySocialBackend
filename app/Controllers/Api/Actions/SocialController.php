<?php

namespace App\Controllers\Api\Actions;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class SocialController extends BaseController
{
    use ResponseTrait;

    /**
     * ✅ 1. TOGGLE FOLLOW (Smart Version)
     * Handles both User IDs and Channel IDs automatically
     */
    public function toggleFollow()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $idFromApp     = $this->request->getVar('user_id'); // Can be User ID or Channel ID
        
        $uri = $this->request->getUri()->getPath();
        $isUnfollowAction = str_contains($uri, 'unfollow');

        if (!$idFromApp) {
            return $this->fail('Target ID is required', 400);
        }

        $db = \Config\Database::connect();
        
        /**
         * 🧠 SMART LOGIC: 
         * Check if the ID belongs to a channel. If yes, get the owner's user_id.
         */
        $channel = $db->table('channels')->select('user_id')->where('id', $idFromApp)->get()->getRow();
        $targetUserId = $channel ? $channel->user_id : $idFromApp;

        // Self-follow check
        if ($targetUserId == $currentUserId) {
            return $this->fail('You cannot follow yourself', 400);
        }

        $db->transStart();

        $builder = $db->table('follows');
        $existing = $builder->where([
            'follower_id'  => $currentUserId,
            'following_id' => $targetUserId
        ])->get()->getRow();

        if ($isUnfollowAction) {
            if ($existing) {
                $builder->where('id', $existing->id)->delete();
                
                // Update Users Table Counts
                $db->table('users')->where('id', $currentUserId)->decrement('following_count');
                $db->table('users')->where('id', $targetUserId)->decrement('followers_count');
                
                // Reset mutual status
                $db->table('follows')->where(['follower_id' => $targetUserId, 'following_id' => $currentUserId])->update(['is_mutual' => 0]);
            }
        } else {
            if (!$existing) {
                $reverseFollow = $db->table('follows')->where(['follower_id' => $targetUserId, 'following_id' => $currentUserId])->get()->getRow();
                $isMutual = $reverseFollow ? 1 : 0;

                $builder->insert([
                    'follower_id'  => $currentUserId,
                    'following_id' => $targetUserId,
                    'is_mutual'    => $isMutual,
                    'created_at'   => date('Y-m-d H:i:s')
                ]);

                // Update Users Table Counts
                $db->table('users')->where('id', $currentUserId)->increment('following_count');
                $db->table('users')->where('id', $targetUserId)->increment('followers_count');

                if ($isMutual) {
                    $db->table('follows')->where(['follower_id' => $targetUserId, 'following_id' => $currentUserId])->update(['is_mutual' => 1]);
                }

                // 🔔 Trigger Notification
                $this->notification->send($targetUserId, $currentUserId, 'follow', 'user', $targetUserId);
            }
        }

        $db->transComplete();

        // Fresh stats return karein taaki UI real-time update ho
        $freshStats = $db->table('users')->select('followers_count, following_count')->where('id', $targetUserId)->get()->getRow();

        return $this->respond([
            'success'         => true, 
            'is_following'    => !$isUnfollowAction, 
            'target_user_id'  => (string)$targetUserId, // Confirming which ID was processed
            'followers_count' => (int)($freshStats->followers_count ?? 0),
            'following_count' => (int)($freshStats->following_count ?? 0)
        ]);
    }

    /**
     * ✅ 2. TOGGLE BLOCK (Smart Version)
     */
    public function toggleBlock()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $idFromApp     = $this->request->getVar('entity_id'); 
        $action        = $this->request->getVar('action');

        if (!$idFromApp || !in_array($action, ['block', 'unblock'])) {
            return $this->fail('Invalid parameters', 400);
        }

        $db = \Config\Database::connect();
        
        // 🧠 SMART LOGIC: Resolve User ID even for Block
        $channel = $db->table('channels')->select('user_id')->where('id', $idFromApp)->get()->getRow();
        $targetUserId = $channel ? $channel->user_id : $idFromApp;

        if ($targetUserId == $currentUserId) {
            return $this->fail('Action not allowed on self', 400);
        }

        $db->transStart();

        if ($action === 'block') {
            // Check if already blocked to avoid duplicates
            $exists = $db->table('blocks')->where(['blocker_id' => $currentUserId, 'blocked_entity_id' => $targetUserId])->countAllResults();
            
            if (!$exists) {
                $db->table('blocks')->insert([
                    'blocker_id'        => $currentUserId,
                    'blocked_entity_id' => $targetUserId,
                    'blocked_type'      => 'user',
                    'created_at'        => date('Y-m-d H:i:s')
                ]);

                /** * 🧹 AUTO-CLEANUP: 
                 * Block karte hi Follow relations khatam kar do dono side se
                 */
                $db->table('follows')->groupStart()
                    ->where(['follower_id' => $currentUserId, 'following_id' => $targetUserId])
                    ->orWhere(['follower_id' => $targetUserId, 'following_id' => $currentUserId])
                ->groupEnd()->delete();
                
                // Optional: Yahan counts ko bhi decrement karne ka logic daal sakte hain
            }
        } else {
            $db->table('blocks')->where(['blocker_id' => $currentUserId, 'blocked_entity_id' => $targetUserId])->delete();
        }

        $db->transComplete();

        return $this->respond(['success' => true, 'message' => ucfirst($action) . "ed successfully"]);
    }
}

