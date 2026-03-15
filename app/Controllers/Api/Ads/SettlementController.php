<?php

namespace App\Controllers\Api\Ads;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class SettlementController extends BaseController {
    use ResponseTrait;
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        // Currency helper ko yahan load kar liya taaki conversion kaam kare
        helper(['currency']); 
    }

    /**
     * 💰 1. GET WALLET SUMMARY (Economy Logic)
     * Yeh App par balance dikhane ke liye hai.
     */
    public function get_wallet_summary() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();

        // User ki preferred currency aur wallet balance uthao
        $user = $this->db->table('users')->select('preferred_currency')->where('id', $userId)->get()->getRow();
        $wallet = $this->db->table('creator_wallets')->where('user_id', $userId)->get()->getRow();

        $rawBalance = $wallet ? (float)$wallet->balance : 0.00;
        $currency = ($user && $user->preferred_currency) ? $user->preferred_currency : 'INR';

        // Unsettled/Pending kamayi (Jo aaj hui hai par wallet mein nahi gayi)
        $pending = $this->db->table('creator_earnings')
                            ->selectSum('amount')
                            ->where(['user_id' => $userId, 'is_settled' => 0, 'status' => 'approved'])
                            ->get()
                            ->getRow();
        
        $rawPending = $pending ? (float)$pending->amount : 0.00;

        return $this->respond([
            'status' => true,
            'data' => [
                'main_wallet' => [
                    'raw_amount' => $rawBalance,
                    'formatted' => format_currency($rawBalance, $currency),
                    'currency' => $currency
                ],
                'pending_earnings' => [
                    'raw_amount' => $rawPending,
                    'formatted' => format_currency($rawPending, $currency),
                ],
                'exchange_rate_applied' => convert_amount(1, $currency)
            ]
        ]);
    }

    /**
     * 📅 2. SETTLE EARNINGS (Settlement Logic)
     * Yeh balance ko 'creator_earnings' se 'creator_wallets' mein move karta hai.
     */
    public function settle_earnings() {
        $secret = $this->request->getVar('secret');
        if ($secret !== 'MySocial_Secure_786') {
            return $this->failUnauthorized('Access Denied: Invalid Secret Key');
        }

        $earnings = $this->db->table('creator_earnings')
                             ->select('user_id, SUM(amount) as total_daily_amount')
                             ->where(['status' => 'approved', 'is_settled' => 0])
                             ->groupBy('user_id')
                             ->get()
                             ->getResultArray();

        if (empty($earnings)) {
            return $this->respond(['status' => true, 'message' => 'Nothing to settle.']);
        }

        $this->db->transStart();
        foreach ($earnings as $row) {
            $userId = $row['user_id'];
            $amount = (float)$row['total_daily_amount'];

            // Wallet update ya create logic
            $walletExists = $this->db->table('creator_wallets')->where('user_id', $userId)->countAllResults();

            if ($walletExists > 0) {
                $this->db->table('creator_wallets')
                         ->where('user_id', $userId)
                         ->set('balance', "balance + $amount", false)
                         ->update();
            } else {
                $this->db->table('creator_wallets')->insert([
                    'user_id' => $userId,
                    'balance' => $amount,
                    'currency' => 'INR',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            // Mark as settled
            $this->db->table('creator_earnings')
                     ->where(['user_id' => $userId, 'status' => 'approved', 'is_settled' => 0])
                     ->update(['is_settled' => 1, 'settled_at' => date('Y-m-d H:i:s')]);
        }
        $this->db->transComplete();

        return $this->respond(['status' => true, 'message' => 'Settlement completed.']);
    }
}
