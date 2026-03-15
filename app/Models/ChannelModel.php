<?php

namespace App\Models;

use CodeIgniter\Model;

class ChannelModel extends Model
{
    protected $table            = 'channels';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array'; 
    protected $useSoftDeletes   = false;   
    
    protected $protectFields    = true;
    
    protected $allowedFields    = [
        'user_id', 
        'unique_id', // ✅ FIXED: Ye missing tha, ab save hoga
        'handle', 
        'last_handle_update',
        'name', 
        'description', 
        'about_text',
        'avatar', 
        'cover_photo', 
        'category',
        'creator_level',
        'videos_count', 
        'total_views',
        'is_verified',
        'trust_score',
        'strikes_count',
        'monetization_status',       
        'monetization_reason',       
        'monetization_apply_count',  
        'monetization_applied_date', 
        'is_monetization_enabled'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation Rules
    protected $validationRules = [
        'handle'    => 'required|is_unique[channels.handle,id,{id}]|min_length[3]|max_length[60]',
        'name'      => 'required|min_length[3]|max_length[120]',
        'user_id'   => 'required|is_unique[channels.user_id,id,{id}]',
        'unique_id' => 'required|is_unique[channels.unique_id,id,{id}]' // ✅ Added security check
    ];

    protected $validationMessages = [
        'handle' => [
            'is_unique' => 'This handle is already taken.'
        ],
        'user_id' => [
            'is_unique' => 'This user already has a creator profile.'
        ],
        'unique_id' => [
            'is_unique' => 'Internal Error: Smart ID collision.'
        ]
    ];
}

