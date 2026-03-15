<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Admin\StatsModel; // Tera purana stats model

class Dashboard extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Helpers for dates and permissions (Sirf 'dashboard' likha hai yahan)
        helper(['url', 'permission_helper', 'dashboard']); 
    }

    public function index()
    {
        $roleId = session()->get('role_id');
        
        // 1. Top Summary Cards (Tera purana logic)
        $statsModel = new StatsModel();
        $data = $statsModel->getDashboardCounts() ?? []; 
        $data['title'] = 'Admin Dashboard';

        // ==========================================
        // 🚀 LIVE ACTIVITY FEEDS (PERMISSION BASED)
        // ==========================================

        // 🟢 1. USER ONBOARDING (Permissions: users.view, kyc.view)
        if (has_permission('users.view') || $roleId == 1) {
            $data['new_users'] = $this->db->table('users')
                // FIXED: email_verified add kiya hai
                ->select('id, name, username, avatar, email_verified, created_at')
                ->where('is_deleted', 0)
                ->orderBy('created_at', 'DESC')->limit(4)->get()->getResult();
        }

        if (has_permission('kyc.view') || has_permission('users.edit') || $roleId == 1) {
            $data['pending_kyc'] = $this->db->table('user_kyc_details k')
                ->select('k.id, k.full_name, k.document_type, k.submitted_at, u.username, u.avatar')
                ->join('users u', 'u.id = k.user_id', 'left')
                ->where('k.status', 'PENDING')
                ->orderBy('k.submitted_at', 'DESC')->limit(4)->get()->getResult();
        }

        // 🟢 2. CONTENT ACTIVITY (Permissions: videos.view, reels.view)
        if (has_permission('videos.view') || $roleId == 1) {
            $data['latest_videos'] = $this->db->table('videos v')
                // FIXED: v.status add kiya hai
                ->select('v.id, v.title, v.thumbnail_url, v.status, v.created_at, c.name as channel_name')
                ->join('channels c', 'c.id = v.channel_id', 'left')
                ->orderBy('v.created_at', 'DESC')->limit(4)->get()->getResult();
        }

        if (has_permission('reels.view') || $roleId == 1) {
            $data['latest_reels'] = $this->db->table('reels r')
                // FIXED: r.status add kiya hai
                ->select('r.id, r.caption, r.thumbnail_url, r.status, r.created_at, u.username')
                ->join('users u', 'u.id = r.user_id', 'left')
                ->orderBy('r.created_at', 'DESC')->limit(4)->get()->getResult();
        }

        // 🟢 3. MODERATION & SAFETY (Permissions: reports.manage, strikes.view)
        if (has_permission('reports.manage') || $roleId == 1) {
            $data['pending_reports'] = $this->db->table('reports r')
                ->select('r.id, r.reportable_type, r.reason, r.created_at, u.username as reporter')
                ->join('users u', 'u.id = r.reporter_id', 'left')
                ->where('r.status', 'pending')
                ->orderBy('r.created_at', 'DESC')->limit(4)->get()->getResult();
        }

        if (has_permission('strikes.view') || has_permission('strikes.manage') || $roleId == 1) {
            $data['recent_strikes'] = $this->db->table('channel_strikes s')
                // FIXED: content_type, content_id, evidence_url, video_title, reel_caption add kiya hai
                ->select('s.id, s.type, s.content_type, s.content_id, s.evidence_url, s.reason, s.status, s.report_source, s.created_at, c.name as channel_name, v.title as video_title, r.caption as reel_caption')
                ->join('channels c', 'c.id = s.channel_id', 'left')
                ->join('videos v', "s.content_type = 'VIDEO' AND v.id = s.content_id", 'left')
                ->join('reels r', "s.content_type = 'REEL' AND r.id = s.content_id", 'left')
                ->orderBy('s.created_at', 'DESC')->limit(4)->get()->getResult();
        }

        // 🟢 4. FINANCE & ADS (Permissions: withdrawals.view, ads.view, channels.monetization)
        if (has_permission('withdrawals.view') || $roleId == 1) {
            $data['pending_withdrawals'] = $this->db->table('withdrawals w')
                // FIXED: w.status add kiya hai
                ->select('w.id, w.amount, w.payment_method, w.status, w.created_at, u.username')
                ->join('users u', 'u.id = w.user_id', 'left')
                ->where('w.status', 'pending')
                ->orderBy('w.created_at', 'DESC')->limit(4)->get()->getResult();
        }

        if (has_permission('ads.view') || $roleId == 1) {
            $data['recent_ads'] = $this->db->table('ads a')
                ->select('a.id, a.title, a.budget, a.status, a.created_at, u.username as advertiser')
                ->join('users u', 'u.id = a.advertiser_id', 'left')
                ->orderBy('a.created_at', 'DESC')->limit(4)->get()->getResult();
        }

        if (has_permission('channels.monetization') || has_permission('channels.view') || $roleId == 1) {
            $data['monetization_requests'] = $this->db->table('channels')
                ->select('id, name, handle, avatar, monetization_applied_date')
                ->where('monetization_status', 'PENDING')
                ->orderBy('monetization_applied_date', 'DESC')->limit(4)->get()->getResult();
        }

        // 🟢 5. SYSTEM AUDIT LOGS (Super Admin Only)
        if ($roleId == 1) {
            $data['admin_logs'] = $this->db->table('admin_logs l')
                ->select('l.action, l.note, l.created_at, u.username as admin_name')
                ->join('users u', 'u.id = l.user_id', 'left')
                ->orderBy('l.created_at', 'DESC')->limit(5)->get()->getResult();
        }

        return view('admin/dashboard', $data);
    }
}
