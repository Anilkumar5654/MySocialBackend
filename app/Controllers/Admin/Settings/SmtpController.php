<?php

namespace App\Controllers\Admin\Settings;

use App\Controllers\BaseController;

class SmtpController extends BaseController {

    protected $db;

    public function __construct() {
        // Database connection establish karna
        $this->db = \Config\Database::connect();
    }

    /**
     * 📧 View Logic: SMTP Settings Dikhana
     */
    public function index() {
        // Sirf 'email' group ki settings fetch karo
        $query = $this->db->table('system_settings')
                         ->where('setting_group', 'email')
                         ->get()
                         ->getResultArray();
        
        // Data ko Key => Value format mein convert karo (View mein asani ke liye)
        $settings = [];
        foreach ($query as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // View load karna aur data pass karna
        return view('admin/settings/smtp_config', ['settings' => $settings]);
    }

    /**
     * 💾 Update Logic: SMTP Settings Save Karna
     */
    public function update() {
        // Form se saara POST data lena
        $data = $this->request->getPost();

        // CSRF Token ko data se hatana (agar CI4 default handling ke alawa hai)
        unset($data['csrf_test_name']); 

        $updatedCount = 0;

        foreach ($data as $key => $value) {
            // Check karein ki kya yeh key hamari table mein exist karti hai
            $exists = $this->db->table('system_settings')
                               ->where('setting_key', $key)
                               ->countAllResults();

            if ($exists > 0) {
                // Agar exist karti hai toh update karo
                $this->db->table('system_settings')
                         ->where('setting_key', $key)
                         ->update(['setting_value' => $value]);
                $updatedCount++;
            }
        }

        if ($updatedCount > 0) {
            return redirect()->back()->with('success', 'SMTP Configuration updated successfully!');
        } else {
            return redirect()->back()->with('error', 'No settings were updated.');
        }
    }
}
