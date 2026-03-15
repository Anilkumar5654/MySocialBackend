<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class StatsModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getDashboardCounts()
    {
        // 💰 Revenue calculate karne ka logic (ad_views table se cost ka sum)
        $revenueRow = $this->db->table('ad_views')->selectSum('cost')->get()->getRow();
        $totalRevenue = $revenueRow ? $revenueRow->cost : 0;

        return [
            // Tere purane stats
            'total_users'     => $this->db->table('users')->where('is_deleted', 0)->countAllResults(), // is_deleted = 0 lagana behtar hai
            'total_videos'    => $this->db->table('videos')->countAllResults(),
            'total_reels'     => $this->db->table('reels')->countAllResults(),
            'pending_reports' => $this->db->table('reports')->where('status', 'pending')->countAllResults(),
            
            // 🚀 Naye stats jo dashboard.php ko chahiye the:
            'total_channels'  => $this->db->table('channels')->countAllResults(),
            'total_revenue'   => $totalRevenue,
        ];
    }
}
