<?php

namespace App\Controllers\Admin\Settings;

use App\Controllers\BaseController;

class UploadController extends BaseController {

    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        
        // 🔥 Strict Type Fix: Logger crash rokne ke liye
        if (!defined('CI_DEBUG')) {
            define('CI_DEBUG', true);
        }
    }

    /**
     * ☁️ View Logic: Saari relevant settings load karein
     */
    public function index() {
        // Database se saari settings uthao
        $query = $this->db->table('system_settings')->get()->getResultArray();
        
        $settings = [];
        // ✅ Null safety: Agar database khali hai toh error na aaye
        if (!empty($query)) {
            foreach ($query as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }

        return view('admin/settings/upload_settings', ['settings' => $settings]);
    }

    /**
     * 💾 Update Logic: FFmpeg, Cloud aur Limits ko sync karein
     */
    public function update() {
        $data = $this->request->getPost();
        $csrfTokenName = csrf_token();
        $updatedCount = 0;

        // ✅ In prefixes se shuru hone wali fields hi save hongi
        $allowedPrefixes = ['max_', 'allowed_', 'reel_', 'do_', 'cloud_', 'ffmpeg_'];

        // Check if data exists to avoid count errors
        if (empty($data)) {
            return redirect()->back()->with('error', 'No data received.');
        }

        foreach ($data as $key => $value) {
            // CSRF aur security tokens ko ignore karo
            if ($key === $csrfTokenName || $key === 'csrf_test_name' || $key === '_method') continue;

            // ✅ Prefix Check: Kya ye key allowed hai?
            $isValidKey = false;
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($key, $prefix)) {
                    $isValidKey = true;
                    break;
                }
            }

            if ($isValidKey) {
                // Sahi format mein data clean karo
                $val = is_array($value) ? implode(',', $value) : trim((string)$value);
                
                // ✅ Check if setting exists in DB
                $builder = $this->db->table('system_settings');
                $existingRow = $builder->where('setting_key', $key)->get()->getRow();

                if ($existingRow) {
                    // 1. UPDATE Existing
                    $this->db->table('system_settings')
                             ->where('setting_key', $key)
                             ->update([
                                 'setting_value' => $val,
                                 'updated_at'    => date('Y-m-d H:i:s')
                             ]);
                } else {
                    // 2. INSERT New (Dynamic Grouping)
                    $group = 'upload';
                    if (str_starts_with($key, 'ffmpeg_')) {
                        $group = 'ffmpeg';
                    } elseif (str_starts_with($key, 'do_') || str_starts_with($key, 'cloud_')) {
                        $group = 'storage';
                    } elseif (str_starts_with($key, 'reel_')) {
                        $group = 'general';
                    }

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
            return redirect()->back()->with('success', "🚀 Engine Update: {$updatedCount} system rules applied successfully!");
        }

        return redirect()->back()->with('error', 'No valid settings were detected for update.');
    }
}
