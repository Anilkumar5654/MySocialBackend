<?php

namespace App\Controllers\Api\System;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class ConfigController extends ResourceController
{
    use ResponseTrait;
    
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * ⚙️ GET /api/system/config
     * Sirf global upload limits aur maintenance status return karega.
     */
    public function index()
    {
        // 1. Database se sirf global settings uthao
        $builder = $this->db->table('system_settings');
        $builder->whereIn('setting_key', [
            'max_upload_size_mb',
            'allowed_video_formats',
            'reel_max_size_mb',
            'reel_max_duration_sec',
            'cloud_storage_enabled'
        ]);

        $query = $builder->get()->getResultArray();
        
        $settings = [];
        foreach ($query as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // 2. Structured Response (No User Permissions Here)
        $response = [
            'status' => true,
            'message' => 'System configuration loaded',
            
            'upload_config' => [
                'max_size_video_mb' => (int)($settings['max_upload_size_mb'] ?? 500),
                'max_size_reel_mb'  => (int)($settings['reel_max_size_mb'] ?? 50),
                'max_duration_reel_sec' => (int)($settings['reel_max_duration_sec'] ?? 60),
                'allowed_formats'   => explode(',', $settings['allowed_video_formats'] ?? 'mp4,mov'),
                'cloud_enabled'     => filter_var($settings['cloud_storage_enabled'] ?? 'false', FILTER_VALIDATE_BOOLEAN)
            ],

            'system_info' => [
                'maintenance_mode' => false, 
                'force_update'     => false,
                'min_app_version'  => '1.0.0'
            ]
        ];

        return $this->respond($response);
    }
}

