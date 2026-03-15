<?php

namespace App\Controllers\Admin\Finance;

use App\Controllers\BaseController;

class Withdrawals extends BaseController
{
    /**
     * 1. LIST VIEW: UTR, Channel aur Handle search support
     */
    public function index()
    {
        if (!has_permission('withdrawals.view') && session()->get('role_id') != 1) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $search = $this->request->getGet('search');
        $builder = $this->db->table('withdrawals as w')
            ->select('w.*, c.name as channel_name, c.handle')
            ->join('channels as c', 'c.user_id = w.user_id', 'left')
            ->orderBy('w.created_at', 'DESC');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('c.name', $search)
                ->orLike('c.handle', $search)
                ->orLike('w.id', $search)
                ->orLike('w.transaction_id', $search) 
            ->groupEnd();
        }

        return view('admin/finance/withdrawals/index', [
            'title' => 'Withdrawals',
            'requests' => $builder->get()->getResult()
        ]);
    }

    /**
     * 2. SINGLE VIEW: KYC Details ke saath
     */
    public function view($id)
    {
        if (!has_permission('withdrawals.view') && session()->get('role_id') != 1) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $request = $this->db->table('withdrawals as w')
            ->select('
                w.*, 
                c.name as channel_name, c.handle, c.avatar, 
                u.email, u.phone, u.kyc_status, 
                k.full_name as kyc_real_name, k.document_type,
                s.upi_id, s.bank_name, s.account_number, s.routing_number as ifsc, s.account_holder_name
            ')
            ->join('channels as c', 'c.user_id = w.user_id', 'left')
            ->join('users as u', 'u.id = w.user_id', 'left')
            ->join('user_kyc_details as k', 'k.user_id = w.user_id', 'left') 
            ->join('user_payout_settings as s', 's.user_id = w.user_id', 'left')
            ->where('w.id', $id)
            ->get()->getRow();

        if (!$request) return redirect()->to('admin/finance/withdrawal')->with('error', 'Request Not Found');

        return view('admin/finance/withdrawals/view', ['title' => 'Payout Details', 'r' => $request]);
    }

    /**
     * 3. APPROVE PAYOUT: With Duplicate & Format Security
     */
    public function approve()
    {
        if (!has_permission('withdrawals.action') && session()->get('role_id') != 1) {
            return redirect()->back()->with('error', 'Permission Denied');
        }

        $id = $this->request->getPost('id');
        // Clean the UTR: Remove spaces, non-alphanumeric and convert to Uppercase
        $utr = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($this->request->getPost('utr_number'))));

        // 🛡️ SECURITY 1: Basic Validation
        if (empty($utr) || strlen($utr) < 8 || strlen($utr) > 22) {
            return redirect()->back()->with('error', 'Invalid UTR! 8-22 Characters required (No spaces/symbols).');
        }

        // 🛡️ SECURITY 2: Duplicate UTR Check (Prevention of double payout error)
        $isDuplicate = $this->db->table('withdrawals')
                                ->where('transaction_id', $utr)
                                ->where('status', 'completed')
                                ->get()->getRow();

        if ($isDuplicate) {
            return redirect()->back()->with('error', "FATAL ERROR: This UTR ($utr) was already used for Request #{$isDuplicate->id}.");
        }

        // 🛡️ SECURITY 3: Status Check
        $check = $this->db->table('withdrawals')->where('id', $id)->get()->getRow();
        if (!$check || $check->status != 'pending') {
            return redirect()->back()->with('error', 'Request is already processed or invalid.');
        }

        // Processing
        $this->db->table('withdrawals')->where('id', $id)->update([
            'status'         => 'completed',
            'transaction_id' => $utr,
            'processed_at'   => date('Y-m-d H:i:s'),
            'admin_notes'    => 'Payout approved and paid.'
        ]);

        log_action('APPROVE_WITHDRAWAL', $id, 'withdrawal', "Payout completed via UTR: $utr");

        return redirect()->to('admin/finance/withdrawal')->with('success', "Withdrawal #$id marked as PAID with UTR: $utr");
    }

    /**
     * 4. REJECT PAYOUT: With Auto-Refund Logic
     */
    public function reject()
    {
        if (!has_permission('withdrawals.action') && session()->get('role_id') != 1) {
            return redirect()->back()->with('error', 'Permission Denied');
        }

        $id = $this->request->getPost('id');
        $reason = trim($this->request->getPost('reason'));

        if (empty($reason)) return redirect()->back()->with('error', 'Reason is required for rejection.');

        $this->db->transStart();
        
        $withdrawal = $this->db->table('withdrawals')->where('id', $id)->get()->getRow();

        if (!$withdrawal || $withdrawal->status != 'pending') {
            return redirect()->back()->with('error', 'Request cannot be rejected.');
        }

        // Update status to failed
        $this->db->table('withdrawals')->where('id', $id)->update([
            'status'      => 'failed', 
            'admin_notes' => $reason,
            'processed_at' => date('Y-m-d H:i:s')
        ]);

        // Refund the balance
        $this->db->table('creator_wallets')
                 ->where('user_id', $withdrawal->user_id)
                 ->increment('balance', $withdrawal->amount);

        log_action('REJECT_WITHDRAWAL', $id, 'withdrawal', "Rejected. Reason: $reason");

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Critical Error: Refund process failed.');
        }

        return redirect()->to('admin/finance/withdrawal')->with('success', 'Withdrawal Rejected & Amount Refunded successfully.');
    }
}
