<?php

namespace App\Models;

use CodeIgniter\Model;

class AdModel extends Model
{
    protected $table = 'ads';
    protected $primaryKey = 'id';
    protected $returnType = 'object'; 
    protected $allowedFields = [
        'advertiser_id', 'title', 'description', 'media_url', 'media_type', 
        'target_url', 'cta_label', 'placement', 'budget', 'spent', 
        'clicks', 'impressions', 'status', 'bid_type', 'bid_amount', 
        'targeting_data', 'admin_rejection_reason', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true;

    // 🔥 Helper Function: Users table join karne ke liye
    public function getAdsWithUser()
    {
        $this->select('ads.*, users.username, users.name, users.avatar, users.email');
        $this->join('users', 'users.id = ads.advertiser_id', 'left');
        return $this; // Return $this taaki chain kar sakein (where, orderBy, paginate)
    }
}
