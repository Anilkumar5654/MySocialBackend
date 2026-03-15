<?php

namespace App\Controllers\Api\Ads;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class SettingsController extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        // DB Connection
        $this->db = \Config\Database::connect();
        
        // Helper call with correct syntax
        helper(['media', 'number', 'text']); 
    }

    /**
     * ✅ GET ADS CONFIGURATION SUMMARY
     * Route: GET /api/ads/settings-summary
     */
    public function summary()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) {
            return $this->failUnauthorized('User ID missing.');
        }

        // 1. Dono tables se settings fetch karna aur merge karna
        $settings = [];

        // Ad Network Settings (Provider info, Meta IDs etc.)
        $netQuery = $this->db->table('ad_network_settings')->get()->getResultArray();
        foreach ($netQuery as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // General Ad Settings (Gaps, Player settings etc.)
        $finQuery = $this->db->table('ad_settings')->get()->getResultArray();
        foreach ($finQuery as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // 2. Logic Check: Active Provider
        $activeProvider = $settings['active_ad_provider'] ?? 'internal';

        return $this->respond([
            'status' => true,
            'message' => 'Configuration Loaded Successfully',
            'data' => [
                'global_ad_status'   => $settings['global_ad_status'] ?? 'active',
                'active_ad_provider' => $activeProvider,

                // --- Meta Configuration (For SDK loading) ---
                'meta_config' => [
                    'fb_app_id'          => $settings['fb_app_id'] ?? '',
                    'fb_placement_reels' => $settings['fb_placement_reels'] ?? '',
                    'fb_placement_video' => $settings['fb_placement_video'] ?? '',
                    'fb_test_mode'       => ($settings['fb_test_mode'] ?? '1') === '1',
                ],

                // --- Placement Switches ---
                'placements' => [
                    'enable_reels_ads'      => ($settings['enable_reels_ads'] ?? '1') === '1',
                    'enable_video_feed_ads' => ($settings['enable_video_feed_ads'] ?? '1') === '1',
                    'enable_instream_ads'   => ($settings['enable_instream_ads'] ?? '1') === '1',
                ],

                // --- Frequency/Gaps ---
                'frequency' => [
                    'reels_gap' => (int)($settings['ad_frequency_reels'] ?? 5),
                    'video_gap' => (int)($settings['ad_frequency_video'] ?? 3),
                ],

                // --- Economy & Sharing ---
                'economy' => [
                    'base_currency'         => 'INR',
                    'min_cpc_bid'           => (float)($settings['min_cpc_bid'] ?? 0.50),
                    'creator_share_percent' => (int)($settings['creator_share_percent'] ?? 45),
                ],

                // --- Video Player Settings ---
                'player_settings' => [
                    'instream_skip_seconds' => (int)($settings['instream_skip_seconds'] ?? 5),
                    'instream_skip_enabled' => ($settings['instream_skip_enabled'] ?? '1') === '1',
                ]
            ]
        ]);
    }
}
