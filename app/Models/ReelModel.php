<?php

namespace App\Models;

use CodeIgniter\Model;

class ReelModel extends Model
{
    protected $table            = 'reels';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields    = [
        'unique_id', 'original_content_id', 'user_id', 'channel_id', 'music_id',       
        'video_url', 'thumbnail_url', 'video_hash', 'frame_hashes', 'caption',
        'category', 'duration', 'visibility', 'status', 'scheduled_at',
        'likes_count', 'comments_count', 'shares_count', 'views_count', 'viral_score'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // ✅ FIX: 'required' ko 'permit_empty' kiya taaki partial updates (Worker) na rukein
    protected $validationRules = [
        'unique_id'  => 'permit_empty', 
        'user_id'    => 'permit_empty|integer',
        'video_url'  => 'permit_empty', 
        'duration'   => 'permit_empty|integer',
        'status'     => 'permit_empty|in_list[draft,published,archived,processing,failed,scheduled]' 
    ];

    protected $validationMessages = [];
    protected $skipValidation     = false;
}
