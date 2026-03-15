<?php

namespace App\Controllers\Admin\Settings;

use App\Controllers\BaseController;

class PointsController extends BaseController {

    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    public function index() {
        $query = $this->db->table('system_settings')->get()->getResultArray();
        $settings = [];
        foreach ($query as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return view('admin/settings/points', ['settings' => $settings]);
    }

    public function update() {
        $data = $this->request->getPost();
        $csrfTokenName = csrf_token(); 
        $updatedCount = 0;

        // ✅ 1. Prefix list ekdum sync hai naye keys ke saath
        $allowedPrefixes = [
            'point_', 'points_', 'viral_', 'trust_', 
            'monetization_', 'max_', 'min_', 'ffmpeg_', 'do_'
        ];

        foreach ($data as $key => $value) {
            if ($key === $csrfTokenName || $key === 'csrf_test_name' || $key === '_method') continue;

            $isValidKey = false;
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $isValidKey = true;
                    break;
                }
            }

            if ($isValidKey) {
                $existingRow = $this->db->table('system_settings')->where('setting_key', $key)->get()->getRow();
                $val = trim($value);

                if ($existingRow) {
                    $this->db->table('system_settings')
                             ->where('setting_key', $key)
                             ->update([
                                 'setting_value' => $val,
                                 'updated_at'    => date('Y-m-d H:i:s')
                             ]);
                } else {
                    // ✅ 2. IMPROVED GROUPING LOGIC (Order matters here!)
                    $group = 'general';
                    
                    // Priority 1: Compliance/Penalties
                    if (str_contains($key, 'penalty') || str_contains($key, 'strike')) {
                        $group = 'compliance';
                    }
                    // Priority 2: Trust Recovery (Streak, Feedback, Quiz)
                    elseif (str_starts_with($key, 'points_')) {
                        $group = 'trust_recovery';
                    }
                    // Priority 3: Monetization Rules
                    elseif (str_starts_with($key, 'monetization_') || $key === 'trust_min_to_apply' || $key === 'trust_min_to_keep') {
                        $group = 'monetization';
                    }
                    // Priority 4: Algorithm
                    elseif (str_starts_with($key, 'viral_')) {
                        $group = 'algorithm';
                    }
                    // Priority 5: Earnings
                    elseif (str_starts_with($key, 'point_')) {
                        $group = 'earnings';
                    }
                    // Priority 6: Storage & FFmpeg (Hidden but essential)
                    elseif (str_starts_with($key, 'do_')) { $group = 'storage'; }
                    elseif (str_starts_with($key, 'ffmpeg_')) { $group = 'ffmpeg'; }

                    $this->db->table('system_settings')->insert([
                        'setting_key'   => $key,
                        'setting_value' => $val,
                        'setting_group' => $group,
                        'updated_at'    => date('Y-m-d H:i:s')
                    ]);
                }
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            return redirect()->back()->with('success', "🚀 Engine Update: {$updatedCount} settings saved!");
        }
        return redirect()->back()->with('error', 'No valid settings detected.');
    }
}
