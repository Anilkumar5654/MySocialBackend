<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Throwable;

class WalletController extends BaseController {
    use ResponseTrait;
    
    public function __construct() {
        helper(['currency', 'url', 'media']);
    }

    /**
     * ✨ DYNAMIC LIMIT: Database se setting uthata hai
     */
    private function getMinLimit($currency = 'INR') {
        $db = \Config\Database::connect();
        $setting = $db->table('system_settings')
                      ->where('setting_key', 'min_withdrawal_amount')
                      ->get()->getRow();
                      
        $baseLimit = $setting ? (float)$setting->setting_value : 500.00;

        return ($currency === 'USD') ? 10.00 : $baseLimit;
    }

    // =================================================================
    // 🟢 SECTION A: CREATOR WALLET (EARNINGS, REVENUE & PAYOUTS)
    // =================================================================

    /**
     * ✅ UPDATED HISTORY: Ab isme Earnings + Top Content + Payout Status + KYC Status + Settings hain
     */
    public function history() {
        try {
            $userId = $this->request->getHeaderLine('User-ID');
            if (!$userId) return $this->failUnauthorized();

            $db = \Config\Database::connect();

            // 1. User Profile Sync
            $user = $db->table('users')
                       ->select('preferred_currency, is_payout_setup, kyc_status')
                       ->where('id', $userId)
                       ->get()->getRow();
            
            $prefCurrency = $user->preferred_currency ?? 'INR';
            $isPayoutSetup = (bool)($user->is_payout_setup ?? 0);
            $kycStatus = $user->kyc_status ?? 'NOT_SUBMITTED';

            $wallet = $db->table('creator_wallets')->where('user_id', $userId)->get()->getRow();

            // 2. Payouts History
            $payouts = $db->table('withdrawals')
                          ->select('amount, payment_method as type, status, created_at, "withdrawal" as category')
                          ->where('user_id', $userId)
                          ->orderBy('created_at', 'DESC')
                          ->limit(40) 
                          ->get()->getResultArray();

            foreach ($payouts as &$p) {
                $p['display_amount'] = format_currency($p['amount'], $prefCurrency);
                $p['amount'] = (float)$p['amount']; 
            }

            // 3. 🔥 TOP EARNING CONTENT
            $topEarning = $this->getTopEarningContentLocal($db, $userId, $prefCurrency);

            // 4. 🔥 YESTERDAY SETTLEMENT
            $yesterdayRaw = $this->getLastAdsSettlementLocal($db, $userId);

            // 5. 🔥 SYSTEM SETTINGS (Minimum Limit from DB)
            $minLimit = $this->getMinLimit($prefCurrency);

            return $this->respond([
                'success' => true,
                'status'  => true,
                'user'    => [
                    'isPayoutSetup' => $isPayoutSetup,
                    'kyc_status'    => $kycStatus
                ],
                'settings' => [
                    'min_withdrawal' => $minLimit
                ],
                'wallet'  => [
                    'balance'               => format_currency($wallet->balance ?? 0, $prefCurrency),
                    'raw_balance'           => (float)($wallet->balance ?? 0.00),
                    'yesterday_ads_revenue' => format_currency($yesterdayRaw, $prefCurrency),
                    'last_payout_date'      => $this->getLastPayoutDateLocal($db, $userId),
                ],
                'history' => $payouts,
                'top_earning_content' => $topEarning
            ]);
        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    public function withdraw() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $db = \Config\Database::connect();
        
        $user = $db->table('users')->select('preferred_currency, is_payout_setup, kyc_status')->where('id', $userId)->get()->getRow();
        
        if (!$user->is_payout_setup) return $this->fail('Please setup payout settings first.');
        if ($user->kyc_status !== 'APPROVED') return $this->fail('KYC verification required.');

        $prefCurrency = $user->preferred_currency ?? 'INR';
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $amount = (float)($input['amount'] ?? 0);
        
        // Use Dynamic Min Limit
        $minLimit = $this->getMinLimit($prefCurrency);
        
        if ($amount < $minLimit) return $this->fail("Minimum withdrawal amount is " . format_currency($minLimit, $prefCurrency));
        
        $wallet = $db->table('creator_wallets')->where('user_id', $userId)->get()->getRow();
        if (!$wallet || (float)$wallet->balance < $amount) return $this->fail('Insufficient balance.');
        
        $settings = $db->table('user_payout_settings')->where('user_id', $userId)->get()->getRow();
        if (!$settings || (empty($settings->upi_id) && empty($settings->account_number))) return $this->fail('Setup Payout Settings first.');
        
        $db->transStart();
        $db->table('withdrawals')->insert([
            'user_id'=>$userId,
            'amount'=>$amount,
            'payment_method'=>$settings->payment_method ?? 'upi',
            'status'=>'pending',
            'created_at'=>date('Y-m-d H:i:s')
        ]);
        $db->table('creator_wallets')->where('user_id', $userId)->decrement('balance', $amount);
        $db->transComplete();
        
        if ($db->transStatus() === false) return $this->failServerError('Failed.');
        return $this->respond(['success'=>true,'message'=>'Request sent.']);
    }

    // =================================================================
    // 🔻 SECTION B: SPENDING WALLET (ADS, GIFTS, RECHARGE) - UNTOUCHED
    // =================================================================

    public function get_spending_balance() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $db = \Config\Database::connect();
        $user = $db->table('users')->select('preferred_currency')->where('id', $userId)->get()->getRow();
        $prefCurrency = $user->preferred_currency ?? 'INR';
        $wallet = $db->table('spending_wallets')->where('user_id', $userId)->get()->getRow();
        return $this->respond(['success'=>true,'status'=>true,'data'=>['balance'=>format_currency($wallet->balance ?? 0,$prefCurrency),'raw_balance'=>$wallet?(float)$wallet->balance:0.00,'currency'=>$prefCurrency,'status'=>$wallet?$wallet->status:'active']]);
    }

    public function get_transactions() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $db = \Config\Database::connect();
        $user = $db->table('users')->select('preferred_currency')->where('id', $userId)->get()->getRow();
        $prefCurrency = $user->preferred_currency ?? 'INR';
        $history = $db->table('wallet_transactions')->where(['user_id'=>$userId,'wallet_type'=>'spending'])->orderBy('created_at','DESC')->limit(30)->get()->getResultArray();
        foreach ($history as &$row) {
            $row['display_amount'] = format_currency($row['amount'], $prefCurrency);
            $row['created_at'] = date('d M, h:i A', strtotime($row['created_at']));
        }
        return $this->respond(['success'=>true,'data'=>$history]);
    }

    public function add_money() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $db = \Config\Database::connect();
        $user = $db->table('users')->select('preferred_currency')->where('id', $userId)->get()->getRow();
        $prefCurrency = $user->preferred_currency ?? 'INR';
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $amount = (float)($input['amount'] ?? 0);
        $minRecharge = ($prefCurrency === 'USD') ? 1.00 : 10.00;
        if ($amount < $minRecharge) return $this->fail("Minimum " . format_currency($minRecharge, $prefCurrency) . " required");
        $txnId = 'TXN_' . strtoupper(uniqid()) . mt_rand(1000, 9999);
        return $this->respond(['success'=>true,'data'=>['order_id'=>$txnId,'amount'=>$amount,'currency'=>$prefCurrency]]);
    }

    public function verify_recharge() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $db = \Config\Database::connect();
        $paymentId = $input['payment_id'] ?? null;
        $amount = (float)($input['amount'] ?? 0);
        if (!$paymentId || $amount <= 0) return $this->fail("Invalid payment");
        $exists = $db->table('wallet_transactions')->where('description LIKE', "%$paymentId%")->countAllResults();
        if ($exists > 0) return $this->fail("Processed.");
        $db->transStart();
        if ($db->table('spending_wallets')->where('user_id', $userId)->countAllResults() == 0) {
            $db->table('spending_wallets')->insert(['user_id' => $userId, 'balance' => 0.00, 'currency' => 'INR']);
        }
        $db->table('spending_wallets')->where('user_id', $userId)->increment('balance', $amount);
        $db->table('wallet_transactions')->insert(['user_id'=>$userId,'wallet_type'=>'spending','amount'=>$amount,'type'=>'credit','description'=>"Wallet Recharge (Ref: $paymentId)",'created_at'=>date('Y-m-d H:i:s')]);
        $db->transComplete();
        if ($db->transStatus() === false) return $this->failServerError("Error.");
        $user = $db->table('users')->select('preferred_currency')->where('id', $userId)->get()->getRow();
        $newBalance = $db->table('spending_wallets')->where('user_id', $userId)->get()->getRow()->balance;
        return $this->respond(['success'=>true,'new_balance'=>format_currency($newBalance, $user->preferred_currency ?? 'INR')]);
    }

    // ==========================================
    // 🛠️ INTERNAL HELPERS (Revenue Data)
    // ==========================================

    private function getTopEarningContentLocal($db, $userId, $prefCurrency) {
        $sql = "
            (SELECT v.id, v.title, v.thumbnail_url as thumbnail, 'video' as type, SUM(ce.amount) as revenue, v.views_count as views
            FROM creator_earnings ce JOIN videos v ON ce.content_id = v.id
            WHERE ce.user_id = ? AND ce.content_type = 'video' GROUP BY v.id ORDER BY revenue DESC LIMIT 2)
            UNION ALL
            (SELECT r.id, r.caption as title, r.thumbnail_url as thumbnail, 'reel' as type, SUM(ce.amount) as revenue, r.views_count as views
            FROM creator_earnings ce JOIN reels r ON ce.content_id = r.id
            WHERE ce.user_id = ? AND ce.content_type = 'reel' GROUP BY r.id ORDER BY revenue DESC LIMIT 2)
            ORDER BY revenue DESC LIMIT 3";
        
        $results = $db->query($sql, [$userId, $userId])->getResultArray();
        foreach ($results as &$item) {
            $item['thumbnail'] = get_media_url($item['thumbnail']);
            $item['display_revenue'] = format_currency($item['revenue'], $prefCurrency);
            $item['views'] = (int)$item['views'];
        }
        return $results;
    }

    private function getLastAdsSettlementLocal($db, $userId) {
        $row = $db->table('creator_earnings')->selectSum('amount')
                  ->where(['user_id' => $userId])
                  ->where('DATE(created_at)', date('Y-m-d', strtotime('-1 day')))
                  ->get()->getRow();
        return $row->amount ? (float)$row->amount : 0.00;
    }

    private function getLastPayoutDateLocal($db, $userId) {
        $row = $db->table('withdrawals')->select('created_at')
                  ->where(['user_id' => $userId, 'status' => 'completed'])
                  ->orderBy('id', 'DESC')->limit(1)->get()->getRow();
        return $row ? date('d M Y', strtotime($row->created_at)) : 'No history';
    }
}
