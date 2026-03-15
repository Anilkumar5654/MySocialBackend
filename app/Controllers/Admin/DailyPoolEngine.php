<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DailyPoolEngine extends BaseController {

    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    /**
     * ⚡ MAIN TRIGGER: Admin ise call karega raat ko
     * Input: $_POST['meta_revenue'] (Aaj Meta se kitna paisa aaya)
     */
    public function process_daily_payout() {
        
        // 1. Admin Security Check (Sirf Admin hi run kar sake)
        // if (!$this->isAdmin()) return; 

        // 2. Input: Aaj ki kamai (e.g., 10000 INR or USD)
        $meta_revenue = $this->request->getPost('meta_revenue');
        $payout_date  = date('Y-m-d', strtotime('-1 day')); // Kal ke performance ka paisa aaj milega

        if (!$meta_revenue || $meta_revenue <= 0) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Invalid Revenue Amount']);
        }

        // ---------------------------------------------------------
        // 🛡️ STEP 1: SECURITY & ANTI-BOT CHECK (Clean Data)
        // ---------------------------------------------------------
        // Fake views hatane ke liye hum wo rows ignore karenge jahan
        // points unrealistic hain ya user fraud list me hai.
        // Filhal hum directly calculate karte hain, par future me yahan filter lagega.
        
        // ---------------------------------------------------------
        // 💰 STEP 2: CALCULATE POOL
        // ---------------------------------------------------------
        $creator_share_percentage = 0.55; // 55% Creators ka
        $total_pool_amount = $meta_revenue * $creator_share_percentage;

        // ---------------------------------------------------------
        // 📊 STEP 3: GET SYSTEM TOTAL POINTS (Sabka mila ke)
        // ---------------------------------------------------------
        $query = $this->db->table('creator_daily_points')
                          ->selectSum('quality_points')
                          ->where('date', $payout_date)
                          ->get()
                          ->getRow();

        $system_total_points = $query->quality_points;

        if ($system_total_points <= 0) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'No points found for yesterday.']);
        }

        // ---------------------------------------------------------
        // 🧮 STEP 4: CALCULATE "POINT RATE" (Rate of the Day)
        // ---------------------------------------------------------
        // Aaj 1 point ki kimat kya hai?
        $rate_per_point = $total_pool_amount / $system_total_points;

        // ---------------------------------------------------------
        // 💸 STEP 5: DISTRIBUTE MONEY (Loop through creators)
        // ---------------------------------------------------------
        // Sirf unko paisa milega jinhone kal perform kiya
        $creators = $this->db->table('creator_daily_points')
                             ->where('date', $payout_date)
                             ->where('quality_points >', 0)
                             ->get()
                             ->getResult();

        $this->db->transStart(); // Transaction shuru (Safety ke liye)

        foreach ($creators as $creator) {
            
            // Creator ki kamai
            $earnings = $creator->quality_points * $rate_per_point;

            // A. Wallet Update Karo (Main Paisa)
            // Note: Pehle check karo wallet hai ya nahi, nahi to banao
            $this->updateWallet($creator->user_id, $earnings);

            // B. Earning History Log Karo (Statement ke liye)
            $this->db->table('creator_earnings')->insert([
                'user_id' => $creator->user_id,
                'amount'  => $earnings,
                'source'  => 'daily_pool', // Ad revenue share
                'date'    => date('Y-m-d'), // Aaj credit hua
                'details' => json_encode([
                    'pool_date' => $payout_date,
                    'points' => $creator->quality_points,
                    'rate' => $rate_per_point
                ])
            ]);
        }

        // ---------------------------------------------------------
        // 📝 STEP 6: LOG POOL HISTORY (Admin Record)
        // ---------------------------------------------------------
        // Aapke SQL file me `daily_pool_logs` table hai, usme entry
        $this->db->table('daily_pool_logs')->insert([
            'date' => $payout_date,
            'total_revenue' => $meta_revenue,
            'distributed_amount' => $total_pool_amount,
            'total_system_points' => $system_total_points,
            'rate_per_point' => $rate_per_point,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Database Error']);
        }

        return $this->response->setJSON([
            'status' => 'success', 
            'msg' => 'Payout Distributed Successfully!',
            'stats' => [
                'total_creators_paid' => count($creators),
                'rate_today' => $rate_per_point,
                'pool_size' => $total_pool_amount
            ]
        ]);
    }

    // --- Helper to update wallet ---
    private function updateWallet($user_id, $amount) {
        $sql = "INSERT INTO creator_wallets (user_id, balance, updated_at) 
                VALUES (?, ?, NOW()) 
                ON DUPLICATE KEY UPDATE balance = balance + ?, updated_at = NOW()";
        
        $this->db->query($sql, [$user_id, $amount, $amount]);
    }
}
