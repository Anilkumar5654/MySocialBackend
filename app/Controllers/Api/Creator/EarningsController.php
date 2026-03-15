<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class EarningsController extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * ✅ GET EARNINGS SUMMARY
     * Used by: creator.ts -> getDashboard() / getEarnings()
     */
    public function getDashboard()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized('User not logged in');

        // 1. Wallet Balance
        $wallet = $this->db->table('creator_wallets')
            ->where('user_id', $userId)
            ->get()->getRow();

        // 2. Pending Points (Raat 12 baje settle hone wale)
        $pending = $this->db->table('creator_daily_points')
            ->selectSum('quality_points', 'total_pending')
            ->where(['user_id' => $userId, 'status' => 'PENDING'])
            ->get()->getRow();

        // 3. Current Payout Rate (From Admin Pool)
        $pool = $this->db->table('daily_pool_logs')
            ->select('coin_rate')
            ->orderBy('date', 'DESC')
            ->limit(1)
            ->get()->getRow();
        
        $lastRate = $pool ? round($pool->coin_rate * 1000, 2) : 0;

        // 4. 7 Days Graph Data
        $chart = $this->db->table('creator_daily_points')
            ->select('date, quality_points as points, qualified_views as views')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->limit(7)
            ->get()->getResultArray();

        return $this->respond([
            'success' => true,
            'wallet' => [
                'balance' => (float)($wallet->balance ?? 0.00),
                'pendingPoints' => (int)($pending->total_pending ?? 0),
                'lastSettlement' => (float)($wallet->last_settlement_amount ?? 0.00)
            ],
            'lastRate' => $lastRate,
            'chart_data' => array_reverse($chart)
        ]);
    }

    /**
     * ✅ GET FULL EARNINGS HISTORY
     */
    public function getHistory()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getVar('page') ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $history = $this->db->table('creator_daily_points')
            ->where('user_id', $userId)
            ->orderBy('date', 'DESC')
            ->limit($limit, $offset)
            ->get()->getResultArray();

        return $this->respond([
            'success' => true,
            'data' => $history,
            'hasMore' => count($history) === $limit
        ]);
    }
}
