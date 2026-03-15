<?php

namespace App\Models;

use CodeIgniter\Model;

class VideoModel extends Model
{
    protected $table            = 'videos';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'unique_id',      // ✅ Added for Smart ID
        'user_id', 
        'channel_id', 
        'title', 
        'description', 
        'video_url', 
        'thumbnail_url', 
        'duration', 
        'category', 
        'visibility', 
        'status', 
        'scheduled_at', 
        'likes_count', 
        'dislikes_count', 
        'comments_count', 
        'shares_count', 
        'views_count', 
        'viral_score', 
        'allow_comments', 
        'monetization_enabled'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'unique_id'  => 'required|is_unique[videos.unique_id,id,{id}]', // ✅ Security Check
        'user_id'    => 'required|integer',
        'channel_id' => 'required|integer',
        'title'      => 'required|min_length[3]|max_length[255]',
        'video_url'  => 'required', 
        'visibility' => 'required', 
        'status'     => 'required'
    ];
}

