<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    // ✅ FIXED: 'country' ko yahan add kar diya gaya hai
    protected $allowedFields = [
        'unique_id',
        'username', 'email', 'password', 'name', 'bio', 'avatar', 'cover_photo', 
        'website', 'location', 'district', 'state', 'country', 'latitude', 'longitude', 
        'phone', 'dob', 'gender', 'preferred_currency',
        'email_verified', 'is_verified', 'is_admin', 'is_creator', 'is_banned', 
        'admin_role', 'role_id', 'followers_count', 'following_count', 'posts_count', 
        'reels_count', 'videos_count', 'last_active', 'is_deleted', 'is_private', 
        'allow_comments', 'allow_global', 'allow_likes', 'allow_follows', 
        'allow_mentions', 'allow_video_uploads', 'allow_dm_requests',
        'kyc_status', 'verification_code', 'verification_code_expires_at', 
        'reset_token', 'reset_token_expiry', 'fcm_token' 
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function castUserTypes($user)
    {
        if (!$user) return null;

        $boolFields = [
            'email_verified', 'is_verified', 'is_admin', 'is_creator', 
            'is_deleted', 'is_banned', 'is_private', 'allow_comments',
            'is_following', 'is_followed_by_viewer', 'is_payout_setup',
            'allow_global', 'allow_likes', 'allow_follows',
            'is_requested' // 🔥 Added for Private Account Sync
        ];
        
        foreach ($boolFields as $field) {
            if (isset($user[$field])) {
                $user[$field] = (bool)$user[$field];
            }
        }

        $intFields = [
            'id', 'followers_count', 'following_count', 
            'posts_count', 'reels_count', 'videos_count', 'role_id'
        ];
        foreach ($intFields as $field) {
            if (isset($user[$field])) {
                $user[$field] = (int)$user[$field];
            }
        }

        if (isset($user['latitude'])) $user['latitude'] = (float)$user['latitude'];
        if (isset($user['longitude'])) $user['longitude'] = (float)$user['longitude'];

        return $user;
    }

    public function getProfile(int $targetUserId, ?int $viewerId = null)
    {
        $builder = $this->builder();
        
        // 🔥 Select mein 'country' already hai, toh yahan koi dikkat nahi thi
        $builder->select('
            id, unique_id, username, email, name, dob, gender, bio, avatar, cover_photo, 
            website, location, district, state, country, latitude, longitude, phone, preferred_currency, 
            is_verified, is_creator, is_admin, admin_role,
            followers_count, following_count, posts_count, reels_count, videos_count, 
            last_active, kyc_status, is_private, allow_comments,
            created_at, updated_at
        ');

        if ($viewerId && $viewerId != 0) {
            // ✅ Only 'accepted' means they are actually following
            $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = {$viewerId} AND following_id = users.id AND status = 'accepted') as is_following");
            
            // ✅ Check if follow request is pending
            $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = {$viewerId} AND following_id = users.id AND status = 'pending') as is_requested");
            
            // ✅ Check if target user follows the viewer
            $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = users.id AND following_id = {$viewerId} AND status = 'accepted') as is_followed_by_viewer");
        } else {
            $builder->select("0 as is_following, 0 as is_requested, 0 as is_followed_by_viewer");
        }

        $user = $builder->where('users.id', $targetUserId)
                        ->where('users.is_deleted', 0)
                        ->get()
                        ->getRowArray();
                     
        return $this->castUserTypes($user);
    }
}
