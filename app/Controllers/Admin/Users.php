<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Users extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Sabhi zaroori helpers load kiye (Admin Logs helper integrated)
        helper(['format_helper', 'permission_helper', 'admin_logs_helper', 'text', 'url', 'number', 'media', 'date']);
    }

    // =========================================================================
    // 👤 SECTION 1: USER MANAGEMENT (Full DB Sync)
    // =========================================================================

    public function index()
    {
        if (!has_permission('users.view')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $search   = $this->request->getGet('search');
        $status   = $this->request->getGet('status');
        $verified = $this->request->getGet('verified');
        $kyc      = $this->request->getGet('kyc');

        $builder = $this->db->table('users u');
        $builder->select('u.*, r.role_name');
        $builder->join('admin_roles r', 'r.id = u.role_id', 'left');

        if ($search) {
            $builder->groupStart()
                ->like('u.username', $search)
                ->orLike('u.name', $search)
                ->orLike('u.email', $search)
                ->orLike('u.phone', $search)
                ->orLike('u.unique_id', $search) // ✨ UPGRADE: Unique ID Search added
                ->groupEnd();
        }

        if ($status === 'banned') $builder->where('u.is_banned', 1);
        if ($status === 'active') $builder->where('u.is_banned', 0);
        if ($verified !== null && $verified !== '') $builder->where('u.is_verified', $verified);
        if ($kyc) $builder->where('u.kyc_status', $kyc);

        $data = [
            'users' => $builder->orderBy('u.id', 'DESC')->get()->getResult(),
            'title' => "User Management"
        ];

        return view('admin/users/index', $data);
    }

    public function view($id)
    {
        if (!has_permission('users.view')) return redirect()->back();
        
        // ✨ UPGRADE: Sync with Channels Table (Monetization Status & Enabled Key)
        $user = $this->db->table('users u')
            ->select('u.*, c.id as channel_id, c.unique_id as channel_unique_id, c.trust_score, c.handle, c.name as channel_name, c.monetization_status as channel_monetization, c.is_monetization_enabled, c.strikes_count')
            ->join('channels c', 'c.user_id = u.id', 'left')
            ->where('u.id', $id)
            ->get()->getRow();

        if (!$user) return redirect()->to('admin/users')->with('error', 'User not found.');

        // ✨ UPGRADE: Fetching additional data for the View Screen
        $data['user']         = $user;
        
        // --- FINANCIAL LOGS FILTER UPGRADE ---
        $txnStart = $this->request->getGet('txn_start');
        $txnEnd   = $this->request->getGet('txn_end');
        $txnBuilder = $this->db->table('wallet_transactions')->where('user_id', $id);
        
        if ($txnStart && $txnEnd) {
            $txnBuilder->where("created_at BETWEEN '$txnStart 00:00:00' AND '$txnEnd 23:59:59'");
        } else {
            $txnBuilder->limit(10);
        }
        
        $data['transactions'] = $txnBuilder->orderBy('id', 'DESC')->get()->getResult();
        // --------------------------------------

        $data['strikes']      = $this->db->table('channel_strikes')->where('channel_id', $user->channel_id)->orderBy('id', 'DESC')->get()->getResult();
        
        // ✨ SYNC: Fetching full KYC details for the profile view screen
        $data['kyc_data']     = $this->db->table('user_kyc_details')
            ->where('user_id', $id)
            ->orderBy('id', 'DESC')
            ->get()->getRow();

        // ✨ UPGRADE: Fetching Active Sessions & Security Logs from auth_tokens
        $data['sessions']     = $this->db->table('auth_tokens')
            ->where('user_id', $id)
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->get()->getResult();

        // ✨ UPGRADE: Revenue Dashboard Logic (Sync with View Section)
        $startDate = $this->request->getGet('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $endDate   = $this->request->getGet('end_date') ?: date('Y-m-d');

        $earnings = $this->db->table('creator_earnings')
            ->where('user_id', $id)
            ->whereIn('status', ['approved', 'paid'])
            ->where("created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'")
            ->selectSum('amount')->get()->getRow()->amount ?? 0;

        $payouts = $this->db->table('withdrawals')
            ->where(['user_id' => $id, 'status' => 'completed'])
            ->selectSum('amount')->get()->getRow()->amount ?? 0;

        $pending = $this->db->table('creator_earnings')
            ->where(['user_id' => $id, 'status' => 'pending'])
            ->selectSum('amount')->get()->getRow()->amount ?? 0;

        $cWallet = $this->db->table('creator_wallets')->where('user_id', $id)->get()->getRow();
        $sWallet = $this->db->table('spending_wallets')->where('user_id', $id)->get()->getRow();

        $data['finance_stats'] = [
            'total_earnings'  => $earnings,
            'total_payouts'   => $payouts,
            'pending_amount'  => $pending,
            'creator_balance' => $cWallet->balance ?? 0,
            'spending_balance'=> $sWallet->balance ?? 0
        ];
        
        $data['title'] = "User Profile: " . ($user->name ?: $user->username);
        
        return view('admin/users/view', $data);
    }

    public function edit($id)
    {
        if (!has_permission('users.edit')) return redirect()->back();

        // Security Guard: Only Super Admin can edit Super Admin
        if ($id == 1 && session()->get('id') != 1) {
            return redirect()->to('admin/users')->with('error', 'Super Admin is protected by System.');
        }

        $user = $this->db->table('users')->where('id', $id)->get()->getRow();
        if (!$user) return redirect()->to('admin/users')->with('error', 'User not found.');

        // Fetching Roles for the permissions section
        $roles = $this->db->table('admin_roles')->get()->getResult();

        $data = [
            'user'  => $user,
            'roles' => $roles,
            'title' => "Edit User Profile: @" . $user->username
        ];

        return view('admin/users/edit', $data);
    }

    public function update($id)
    {
        if (!has_permission('users.edit')) return redirect()->back();
        
        // Security Guard: Prevents editing ID 1 by anyone else
        if ($id == 1 && session()->get('id') != 1) {
            return redirect()->back()->with('error', 'Unauthorized Access.');
        }

        // Fetch current data to check for changes
        $currentUser = $this->db->table('users')->where('id', $id)->get()->getRow();
        if (!$currentUser) return redirect()->back()->with('error', 'User not found.');

        // ✨ CATEGORY A: Identity & Profile
        $postData = [
            'name'                => esc($this->request->getPost('name')),
            'username'            => esc($this->request->getPost('username')),
            'email'               => esc($this->request->getPost('email')),
            'phone'               => esc($this->request->getPost('phone')),
            'dob'                 => $this->request->getPost('dob') ?: NULL,
            'gender'              => $this->request->getPost('gender'),
            'bio'                 => esc($this->request->getPost('bio')),
            'website'             => esc($this->request->getPost('website')),
            'location'            => esc($this->request->getPost('location')),
            'district'            => esc($this->request->getPost('district')),
            'state'               => esc($this->request->getPost('state')),
            'country'             => esc($this->request->getPost('country')),
        ];

        // ✨ CATEGORY B: Account Status & Verification
        $postData['email_verified']  = $this->request->getPost('email_verified') ? 1 : 0;
        $postData['is_verified']     = $this->request->getPost('is_verified') ? 1 : 0;
        $postData['is_banned']       = $this->request->getPost('is_banned') ? 1 : 0;
        $postData['kyc_status']      = $this->request->getPost('kyc_status');

        // ✨ CATEGORY C: Permissions & Admin Roles
        // Loop hole check: Admin cannot change their own role unless they are super admin
        if (has_permission('staff.manage') && ($id != session()->get('id'))) {
            $roleId = $this->request->getPost('role_id');
            if ($roleId !== null) {
                $postData['role_id']  = $roleId;
                $postData['is_admin'] = ($roleId > 0) ? 1 : 0;
            }
        }

        $postData['is_creator']      = $this->request->getPost('is_creator') ? 1 : 0;
        $postData['is_private']      = $this->request->getPost('is_private') ? 1 : 0;
        $postData['is_payout_setup'] = $this->request->getPost('is_payout_setup') ? 1 : 0;

        // ✨ CATEGORY E: App Settings & Toggles
        $postData['allow_comments']      = $this->request->getPost('allow_comments');
        $postData['allow_video_uploads'] = $this->request->getPost('allow_video_uploads') ? 1 : 0;
        $postData['allow_dm_requests']   = $this->request->getPost('allow_dm_requests') ? 1 : 0;
        $postData['preferred_currency']  = $this->request->getPost('preferred_currency') ?: 'INR';

        // Password Security: Only update if not empty
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $postData['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        // Validate Unique Constraints before Update
        $existing = $this->db->table('users')
            ->where('id !=', $id)
            ->groupStart()
                ->where('username', $postData['username'])
                ->orWhere('email', $postData['email'])
            ->groupEnd()
            ->get()->getRow();

        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Username or Email already taken by another user.');
        }

        if ($this->db->table('users')->where('id', $id)->update($postData)) {
            log_action('UPDATE_USER_PROFILE', $id, 'users', "Detailed profile update for @" . $postData['username']);
            return redirect()->to('admin/users/view/' . $id)->with('success', 'User profile has been updated successfully.');
        }

        return redirect()->back()->with('error', 'Failed to update user.');
    }

    /**
     * ✨ SECURITY UPGRADE: Force Logout All Sessions
     */
    public function force_logout_all($id) {
        if (!has_permission('users.edit')) return redirect()->back();
        $this->db->table('auth_tokens')->where('user_id', $id)->update(['is_active' => 0, 'logged_out_at' => date('Y-m-d H:i:s')]);
        log_action('FORCE_LOGOUT', $id, 'users', "All sessions terminated by Admin.");
        return redirect()->back()->with('success', 'All active sessions terminated.');
    }

    /**
     * ✨ SECURITY UPGRADE: Trigger Password Reset Email
     */
    public function reset_password_trigger($id) {
        if (!has_permission('users.edit')) return redirect()->back();
        // Trigger logic: reset_token generate and log it
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->db->table('users')->where('id', $id)->update([
            'reset_token' => $otp,
            'reset_token_expiry' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]);
        log_action('RESET_PWD_TRIGGER', $id, 'users', "Manual password reset OTP generated by Admin.");
        return redirect()->back()->with('success', "Password reset OTP ($otp) has been generated for the user.");
    }

    /**
     * ✨ SECURITY UPGRADE: Toggle 2FA Status
     */
    public function toggle_2fa($id) {
        if (!has_permission('users.edit')) return redirect()->back();
        $user = $this->db->table('users')->where('id', $id)->get()->getRow();
        if ($user->two_factor_secret) {
            $this->db->table('users')->where('id', $id)->update(['two_factor_secret' => NULL]);
            log_action('2FA_DISABLE', $id, 'users', "2FA disabled by Admin.");
            return redirect()->back()->with('success', 'Two-Factor Authentication has been DISABLED.');
        } else {
            return redirect()->back()->with('error', 'User has not enabled 2FA. Admin cannot enable it manually.');
        }
    }

    public function toggle_ban($id)
    {
        if (!has_permission('users.ban')) return redirect()->back()->with('error', 'Access Denied');
        if ($id == 1) return redirect()->back()->with('error', 'System Guard: Super Admin cannot be banned.');

        $user = $this->db->table('users')->where('id', $id)->get()->getRow();
        if (!$user) return redirect()->back()->with('error', 'User not found.');

        $newStatus = ($user->is_banned == 1) ? 0 : 1;
        $this->db->table('users')->where('id', $id)->update(['is_banned' => $newStatus]);

        $msg = $newStatus ? "User Banned" : "User Restored";
        log_action('TOGGLE_BAN', $id, 'users', "$msg: @{$user->username}");

        return redirect()->back()->with('success', "Account status updated successfully.");
    }

    public function delete($id)
    {
        if (!has_permission('users.delete') || $id == 1 || $id == session()->get('id')) {
            return redirect()->back()->with('error', 'Action Restricted.');
        }
        
        $this->db->transStart();
        $this->db->table('user_kyc_details')->where('user_id', $id)->delete();
        $this->db->table('auth_tokens')->where('user_id', $id)->delete(); // Cleanup auth
        $this->db->table('users')->where('id', $id)->delete();
        $this->db->transComplete();

        if ($this->db->transStatus() === TRUE) {
            log_action('DELETE_USER', $id, 'users', "Permanently wiped user and associated data.");
            return redirect()->to('admin/users')->with('success', 'User and associated records removed.');
        }

        return redirect()->back()->with('error', 'Deletion failed.');
    }

    // =========================================================================
    // 🪪 SECTION 2: KYC MANAGEMENT
    // =========================================================================

    public function kyc_requests()
    {
        if (!has_permission('users.view')) return redirect()->to('admin/dashboard');

        $filterStatus = $this->request->getGet('filter') ?? 'PENDING';
        $search       = $this->request->getGet('search');

        $builder = $this->db->table('user_kyc_details k');
        $builder->select('k.*, u.username, u.email, u.avatar, u.kyc_status as current_user_status');
        $builder->join('users u', 'u.id = k.user_id');

        if ($filterStatus !== 'ALL') $builder->where('k.status', $filterStatus);
        
        if ($search) {
            $builder->groupStart()->like('u.username', $search)->orLike('k.full_name', $search)->groupEnd();
        }

        $data = [
            'title' => 'KYC Verification Queue',
            'requests' => $builder->orderBy('k.submitted_at', 'ASC')->get()->getResult(),
            'current_filter' => $filterStatus
        ];
        return view('admin/users/kyc/index', $data);
    }

    public function kyc_view($id)
    {
        if (!has_permission('users.view')) return redirect()->back();

        $builder = $this->db->table('user_kyc_details k');
        $builder->select('k.*, u.username, u.name, u.email, u.phone, u.avatar, u.kyc_status as current_user_status');
        // FIX: 'id' in ON clause made unambiguous using 'u.id'
        $builder->join('users u', 'u.id = k.user_id', 'left');
        $builder->where('k.id', $id);
        
        $request = $builder->get()->getRow();

        if (!$request) return redirect()->to('admin/kyc/requests')->with('error', 'Request not found.');

        return view('admin/users/kyc/view', ['request' => $request, 'title' => 'KYC Verification Details']);
    }

    public function kyc_action()
    {
        if (!has_permission('users.edit')) return redirect()->back();

        $kycId  = $this->request->getPost('kyc_id');
        $userId = $this->request->getPost('user_id');
        $action = $this->request->getPost('action'); 
        $reason = $this->request->getPost('rejection_reason');

        $kycSetting = $this->db->table('system_settings')->where('setting_key', 'points_bonus_kyc')->get()->getRow();
        $kycBonus = $kycSetting ? (int)$kycSetting->setting_value : 25;

        $user = $this->db->table('users')->select('kyc_status')->where('id', $userId)->get()->getRow();
        $oldStatus = $user ? $user->kyc_status : 'NOT_SUBMITTED';

        $this->db->transStart();

        if ($action === 'approve') {
            if ($oldStatus === 'APPROVED') return redirect()->back()->with('warning', 'Already Verified.');

            $this->db->table('user_kyc_details')->where('id', $kycId)->update(['status' => 'APPROVED']);
            $this->db->table('users')->where('id', $userId)->update(['kyc_status' => 'APPROVED']);
            // Trust score update in channel
            $this->db->table('channels')->where('user_id', $userId)->set('trust_score', "trust_score + $kycBonus", FALSE)->update();

            log_action('KYC_APPROVE', $userId, 'users', "KYC Approved (+$kycBonus points).");
        } 
        elseif ($action === 'reject') {
            $this->db->table('user_kyc_details')->where('id', $kycId)->update(['status' => 'REJECTED', 'rejection_reason' => $reason]);
            $this->db->table('users')->where('id', $userId)->update(['kyc_status' => 'REJECTED']);

            if ($oldStatus === 'APPROVED') {
                $this->db->table('channels')->where('user_id', $userId)->set('trust_score', "trust_score - $kycBonus", FALSE)->update();
                log_action('KYC_REVOKE', $userId, 'users', "KYC Revoked (-$kycBonus points).");
            } else {
                log_action('KYC_REJECT', $userId, 'users', "KYC Rejected: $reason");
            }
        }

        $this->db->transComplete();
        return redirect()->to('admin/kyc/requests')->with('success', 'KYC processing complete.');
    }
}
