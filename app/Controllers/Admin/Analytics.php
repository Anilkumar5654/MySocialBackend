<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class Analytics extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Helpers load kar rahe hain
        helper(['url', 'format_helper', 'permission_helper']);
    }

    /**
     * 👁️ LOAD MAIN ANALYTICS VIEW
     * Location: /admin/analytics
     */
    public function index()
    {
        // Admin permission check
        if (!function_exists('has_permission') || !has_permission('analytics.view')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied: Super Admin Only.');
        }

        $data = [
            'title' => 'Super Admin Analytics',
            'menu'  => 'analytics'
        ];

        return view('admin/analytics/index', $data);
    }

    /**
     * 📊 FETCH ALL DASHBOARD DATA (JSON API FOR CHARTS & CARDS)
     * Location: /admin/analytics/getDashboardData
     */
    public function getDashboardData()
    {
        try {
            // Get filter from request URL, default is 'lifetime'
            $filter = $this->request->getGet('filter') ?? 'lifetime';
            
            // Default SQL condition
            $dateCondition = "1=1";
            $trendGroup = "DATE(created_at)";

            // 🕒 DYNAMIC DATE FILTER LOGIC
            switch ($filter) {
                case 'last_hour':
                    $dateCondition = "created_at >= NOW() - INTERVAL 1 HOUR";
                    $trendGroup = "DATE_FORMAT(created_at, '%H:%i')"; // Minute-by-minute
                    break;
                case 'today':
                    $dateCondition = "DATE(created_at) = CURDATE()";
                    $trendGroup = "DATE_FORMAT(created_at, '%H:00')"; // Hourly
                    break;
                case 'this_week':
                    $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
                    $trendGroup = "DATE(created_at)"; // Daily
                    break;
                case 'this_month':
                    $dateCondition = "YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";
                    $trendGroup = "DATE(created_at)"; // Daily
                    break;
                case 'this_year':
                    $dateCondition = "YEAR(created_at) = YEAR(CURDATE())";
                    $trendGroup = "DATE_FORMAT(created_at, '%Y-%m')"; // Monthly
                    break;
                case 'lifetime':
                default:
                    $dateCondition = "1=1";
                    $trendGroup = "DATE_FORMAT(created_at, '%Y-%m')"; // Monthly
                    break;
            }

            // =========================================================================
            // 💎 1. TOP ROW CARDS (FINANCIAL METRICS - FILTERED)
            // =========================================================================
            
            // Total Advertiser Revenue
            $revenueQuery = $this->db->query("SELECT SUM(amount) as total FROM deposit_requests WHERE status = 'approved' AND $dateCondition")->getRow();
            $totalRevenue = $revenueQuery ? (float)$revenueQuery->total : 0;

            // Total Creator Payouts
            $payoutQuery = $this->db->query("SELECT SUM(amount) as total FROM withdrawals WHERE status = 'completed' AND $dateCondition")->getRow();
            $totalPayout = $payoutQuery ? (float)$payoutQuery->total : 0;

            // Platform Net Profit
            $viewsProfitQuery = $this->db->query("SELECT SUM(cost - creator_revenue) as profit FROM ad_views WHERE is_settled = 1 AND $dateCondition")->getRow();
            $clicksProfitQuery = $this->db->query("SELECT SUM(cost - creator_revenue) as profit FROM ad_clicks WHERE is_settled = 1 AND $dateCondition")->getRow();
            $netProfit = ($viewsProfitQuery ? (float)$viewsProfitQuery->profit : 0) + ($clicksProfitQuery ? (float)$clicksProfitQuery->profit : 0);

            // Active Ad Campaigns (Real-Time)
            $activeAdsQuery = $this->db->query("SELECT COUNT(*) as count FROM ads WHERE status = 'active'")->getRow();
            $activeCampaigns = $activeAdsQuery ? (int)$activeAdsQuery->count : 0;

            // =========================================================================
            // 💰 2. PLATFORM WALLET BALANCES & PENDING REQUESTS (Real-Time)
            // =========================================================================
            
            // Total spending wallets (Advertisers)
            $spendingWalletQuery = $this->db->query("SELECT SUM(balance) as total FROM spending_wallets")->getRow();
            $totalSpendingBalance = $spendingWalletQuery ? (float)$spendingWalletQuery->total : 0;

            // Total creator wallets (Creators)
            $creatorWalletQuery = $this->db->query("SELECT SUM(balance) as total FROM creator_wallets")->getRow();
            $totalCreatorBalance = $creatorWalletQuery ? (float)$creatorWalletQuery->total : 0;

            // Total Pending Deposits (Advertisers await approval)
            $pendingDepQuery = $this->db->query("SELECT SUM(amount) as total FROM deposit_requests WHERE status = 'pending'")->getRow();
            $totalPendingDeposits = $pendingDepQuery ? (float)$pendingDepQuery->total : 0;

            // Total Pending Withdrawals (Creators await payout)
            $pendingWithQuery = $this->db->query("SELECT SUM(amount) as total FROM withdrawals WHERE status = 'pending'")->getRow();
            $totalPendingWithdrawals = $pendingWithQuery ? (float)$pendingWithQuery->total : 0;

            // =========================================================================
            // 📈 3. CHARTS & GRAPHS DATA (FILTERED & GROUPED)
            // =========================================================================

            // Revenue vs Payout Trend
            $trendQuery = $this->db->query("
                SELECT $trendGroup as date, 
                       SUM(IF(wallet_type = 'creator' AND type = 'credit', amount, 0)) as daily_payout,
                       SUM(IF(wallet_type = 'spending' AND type = 'credit', amount, 0)) as daily_revenue
                FROM wallet_transactions 
                WHERE $dateCondition
                GROUP BY $trendGroup 
                ORDER BY date ASC
            ")->getResultArray();

            // P&L By Model
            $modelsQuery = $this->db->query("SELECT bid_type, SUM(spent) as total_spent FROM ads WHERE $dateCondition GROUP BY bid_type")->getResultArray();

            // =========================================================================
            // 🛡️ 4. INFRASTRUCTURE & RISK SUMMARY (Real-Time)
            // =========================================================================

            // Top 5 Viral Hashtags
            $hashtagsQuery = $this->db->table('hashtags')
                                      ->select('tag, posts_count')
                                      ->orderBy('posts_count', 'DESC')
                                      ->limit(5)
                                      ->get()
                                      ->getResultArray();

            // KYC Bottleneck
            $kycQuery = $this->db->query("SELECT COUNT(*) as count FROM user_kyc_details WHERE status = 'PENDING'")->getRow();
            $pendingKyc = $kycQuery ? (int)$kycQuery->count : 0;

            // Infrastructure Health
            $ffmpegQuery = $this->db->query("SELECT status, COUNT(*) as count FROM video_processing_queue GROUP BY status")->getResultArray();
            $ffmpegStatus = [];
            foreach ($ffmpegQuery as $row) {
                $ffmpegStatus[$row['status']] = (int)$row['count'];
            }

            // Platform Trust (Active Strikes)
            $strikesQuery = $this->db->query("SELECT COUNT(*) as count FROM channel_strikes WHERE status = 'ACTIVE'")->getRow();
            $activeStrikes = $strikesQuery ? (int)$strikesQuery->count : 0;

            // =========================================================================
            // 🚀 ASSEMBLE FINAL JSON RESPONSE
            // =========================================================================
            
            return $this->respond([
                'status' => 'success',
                'data' => [
                    'cards' => [
                        'total_revenue'    => $totalRevenue,
                        'total_payouts'    => $totalPayout,
                        'net_profit'       => $netProfit,
                        'active_campaigns' => $activeCampaigns
                    ],
                    // ✅ ADDED WALLET BALANCES
                    'wallet_balances' => [
                        'total_spending'      => $totalSpendingBalance,
                        'total_creator'       => $totalCreatorBalance,
                        'pending_deposits'    => $totalPendingDeposits,
                        'pending_withdrawals' => $totalPendingWithdrawals
                    ],
                    'charts' => [
                        'revenue_trend' => $trendQuery,
                        'ad_models'     => $modelsQuery
                    ],
                    'summary' => [
                        'viral_hashtags'  => $hashtagsQuery,
                        'pending_kyc'     => $pendingKyc,
                        'ffmpeg_queue'    => $ffmpegStatus,
                        'active_strikes'  => $activeStrikes
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', '[Analytics API] Error: ' . $e->getMessage());
            return $this->failServerError('Something went wrong while fetching analytics data.');
        }
    }
}
