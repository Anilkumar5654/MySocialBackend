<?php

namespace App\Models;

use CodeIgniter\Model;

class AdsModel extends Model
{
    protected $table            = 'ads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object'; // 'object' use karne se view mein $ad->title likhna aasaan hota hai

    protected $allowedFields    = [
        'advertiser_id', 'title', 'description', 'targeting_data',
        'media_url', 'thumbnail_url', 'media_type', 'placement',
        'target_url', 'cta_label', 'cta_style', 'budget', 'daily_limit',
        'bid_type', 'bid_amount', 'spent', 'impressions', 'clicks', 'views',
        'status', 'locked_by', 'locked_at', 'priority_weight',
        'admin_rejection_reason', 'start_date', 'end_date',
        'source_post_id', 'source_type', 'ad_type'
    ];

    // DB handles current_timestamp, lekin CI ko batana zaroori hai agar aap query builder use kar rahe hain
    protected $useTimestamps = true; 
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'advertiser_id' => 'required|numeric',
        'title'         => 'required|min_length[3]',
        'media_url'     => 'required|valid_url',
        'media_type'    => 'required|in_list[image,video]',
        'bid_type'      => 'required|in_list[cpc,cpm,cpv]',
        'budget'        => 'required|decimal',
        'ad_type'       => 'required|in_list[custom_ad,boosted_content]'
    ];
}
