<?php

namespace App\Controllers\Admin\Finance;

use App\Controllers\BaseController;

class Pool extends BaseController {

    protected $db;
    protected $ad_config = [];

    public function __construct() {
        $this->db = \Config\Database::connect();
        $this->load_pool_settings(); // ⚙️ Settings load karne ke liye
    }

    /**
     * ⚙️ Load Dynamic Settings from Database
     */
    private function load_pool_settings() {
        $settings = $this->db->table('ad_settings')->get()->getResultArray();
        foreach ($settings as $row) {
            $this->ad_config[$row['setting_key']] = $row['setting_value'];
        }
    }

    /**
     * 📊 Page: Pool Manager UI
     */
    public function index() {
        $stats = $this->db->table('creator_daily_points')
                      ->selectSum('quality_points')
                      ->where('status', 'PENDING')
                      ->get()->getRow();

        $logs = $this->db->table('daily_pool_logs')
                     ->orderBy('id', 'DESC')
                     ->limit(10)
                     ->get()->getResultArray();

        $data = [
            'yesterday_points' => $stats->quality_points ?? 0, 
            'logs' => $logs,
            // View ko batana ki kitna % share chal raha hai
            'current_share' => (float)($this->ad_config['revenue_share_pool'] ?? 55) 
        ];

        return view('admin/finance/pool/index', $data);
    }

    /**
     * 💰 Action: Distribute Money (Dynamic & Production Ready)
     */
    public function process_daily_payout() {
        if (!$this->request->isAJAX()) return $this->response->setStatusCode(404);

        $meta_revenue = (float)$this->request->getPost('meta_revenue');
        if ($meta_revenue <= 0) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Invalid Revenue Amount']);
        }

        // --- STEP 1: DYNAMIC CALCULATION ---
        // 🚀 Fix: Database se percentage uthana (e.g. 60)
        $share_percent = (float)($this->ad_config['revenue_share_pool'] ?? 55); 
        $creator_multiplier = $share_percent / 100;

        $total_pool_amount = $meta_revenue * $creator_multiplier;
        $platform_profit = $meta_revenue - $total_pool_amount;

        $query = $this->db->table('creator_daily_points')->selectSum('quality_points')->where('status', 'PENDING')->get()->getRow();
        $system_total_points = $query->quality_points ?? 0;

        if ($system_total_points <= 0) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'No PENDING points found.']);
        }

        $coin_rate = $total_pool_amount / $system_total_points;

        // --- STEP 2: DISTRIBUTION ---
        $this->db->transBegin();

        try {
            $creators = $this->db->table('creator_daily_points')
                                 ->where('status', 'PENDING')
                                 ->where('quality_points >', 0)
                                 ->get()->getResult();

            foreach ($creators as $row) {
                $earnings = round(($row->quality_points * $coin_rate), 6);

                // A. Update Wallet
                $this->db->query("INSERT INTO creator_wallets (user_id, balance, currency, status, created_at, updated_at) 
                        VALUES (?, ?, 'USD', 'active', NOW(), NOW()) 
                        ON DUPLICATE KEY UPDATE balance = balance + ?, updated_at = NOW()", [$row->user_id, $earnings, $earnings]);

                // B. Log Earning History
                $this->db->table('creator_earnings')->insert([
                    'user_id'      => $row->user_id,
                    'earning_type' => 'daily_pool_reward',
                    'amount'       => $earnings,
                    'currency'     => 'USD',
                    'status'       => 'approved',
                    'is_settled'   => 1,
                    'settled_at'   => date('Y-m-d H:i:s'),
                    'created_at'   => date('Y-m-d H:i:s')
                ]);
                
                // C. Mark as PAID
                $this->db->table('creator_daily_points')->where('id', $row->id)->update([
                    'final_earnings' => $earnings,
                    'status'         => 'PAID'
                ]);
            }

            // D. Admin Log (Dynamic Percentages)
            $today = date('Y-m-d');
            $this->db->table('daily_pool_logs')->where('date', $today)->delete();

            $this->db->table('daily_pool_logs')->insert([
                'date'                      => $today,
                'meta_revenue'              => $meta_revenue,
                'creator_pool_amount'       => $total_pool_amount,
                'total_system_points'       => $system_total_points,
                'coin_rate'                 => $coin_rate,
                'platform_share_percentage' => (100 - $share_percent), // Dynamic (e.g. 40)
                'platform_profit'           => $platform_profit,
                'is_distributed'            => 1,
                'created_at'                => date('Y-m-d H:i:s')
            ]);

            $this->db->transCommit();
            return $this->response->setJSON(['status' => 'success', 'msg' => "Success! Distributed $$total_pool_amount to creators."]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->response->setJSON(['status' => 'error', 'msg' => $e->getMessage()]);
        }
    }
}
