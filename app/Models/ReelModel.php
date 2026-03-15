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
        'unique_id',      // ✅ Added for Smart ID
        'user_id',
        'channel_id',
        'music_id',       
        'video_url',
        'thumbnail_url',
        'caption',
        'category',
        'duration',       
        'visibility',     
        'status',         
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'viral_score'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'unique_id'  => 'required|is_unique[reels.unique_id,id,{id}]', // ✅ Security Check
        'user_id'    => 'required|integer',
        'video_url'  => 'required', 
        'duration'   => 'required|integer',
        'status'     => 'in_list[draft,published,archived]'
    ];
}

