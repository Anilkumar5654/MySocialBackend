<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Reports extends BaseController
{
    /**
     * 1. REPORTS OVERVIEW
     */
    public function index()
    {
        if (!has_permission('reports.manage')) return redirect()->to('admin/dashboard');

        // Stats for all types (🔥 Channel added)
        $stats = [
            'videos'   => $this->db->table('reports')->where('reportable_type', 'video')->where('status', 'pending')->countAllResults(),
            'reels'    => $this->db->table('reports')->where('reportable_type', 'reel')->where('status', 'pending')->countAllResults(),
            'posts'    => $this->db->table('reports')->where('reportable_type', 'post')->where('status', 'pending')->countAllResults(),
            'comments' => $this->db->table('reports')->where('reportable_type', 'comment')->where('status', 'pending')->countAllResults(),
            'users'    => $this->db->table('reports')->where('reportable_type', 'user')->where('status', 'pending')->countAllResults(),
            'channels' => $this->db->table('reports')->where('reportable_type', 'channel')->where('status', 'pending')->countAllResults(),
        ];

        return view('admin/reports/index', ['title' => 'Reports Overview', 'stats' => $stats]);
    }

    // --- Shortcuts (All 6 Types) ---
    public function videos()   { return $this->_getReportsList('video', 'Video Reports'); }
    public function reels()    { return $this->_getReportsList('reel', 'Reels Reports'); }
    public function posts()    { return $this->_getReportsList('post', 'Post Reports'); }
    public function comments() { return $this->_getReportsList('comment', 'Comment Reports'); }
    public function users()    { return $this->_getReportsList('user', 'User Profile Reports'); }
    public function channels() { return $this->_getReportsList('channel', 'Channel Reports'); } // 🔥 Added Method

    /**
     * 2. DYNAMIC LIST FETCHER
     */
    private function _getReportsList($type, $pageTitle)
    {
        $permissionSlug = "reports.{$type}s.view"; 
        if (!has_permission($permissionSlug) && !has_permission('reports.manage')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        // ==========================================
        // 🚀 AUTO-UNLOCK LOGIC (15 MIN TIMEOUT)
        // ==========================================
        // Agar koi lock 15 min purana hai, usko wapas open kar do.
        $timeoutLimit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $this->db->table('reports')
            ->where('locked_at <', $timeoutLimit)
            ->where('status', 'pending') // Sirf pending wali ko
            ->update(['locked_by' => null, 'locked_at' => null]);
        // ==========================================

        $builder = $this->db->table('reports');
        $builder->select('reports.*, users.username as reporter_name, users.avatar as reporter_avatar');
        $builder->join('users', 'users.id = reports.reporter_id', 'left'); 
        $builder->where('reports.reportable_type', $type);

        $status = service('request')->getGet('status');
        if ($status) {
            $builder->where('reports.status', $status);
        } else {
            $builder->orderBy('reports.status', 'ASC'); 
        }

        $reportsData = $builder->orderBy('reports.created_at', 'DESC')->get()->getResult();

        $stats = [
            'pending'   => $this->db->table('reports')->where('reportable_type', $type)->where('status', 'pending')->countAllResults(),
            'resolved'  => $this->db->table('reports')->where('reportable_type', $type)->where('status', 'resolved')->countAllResults(),
            'dismissed' => $this->db->table('reports')->where('reportable_type', $type)->where('status', 'dismissed')->countAllResults(),
        ];

        // Folder Path: admin/reports/{type}s/list
        return view("admin/reports/{$type}s/list", [
            'title'   => $pageTitle,
            'type'    => $type,
            'reports' => $reportsData,
            'stats'   => $stats 
        ]);
    }

    /**
     * 3. VIEW SINGLE REPORT
     */
    public function view($id)
    {
        $report = $this->db->table('reports')
            ->select('
                reports.*, 
                reporter.username as reporter_name, 
                reporter.avatar as reporter_avatar, 
                reviewer.username as reviewer_name,
                reviewer.name as reviewer_full_name
            ')
            ->join('users as reporter', 'reporter.id = reports.reporter_id', 'left')
            ->join('users as reviewer', 'reviewer.id = reports.reviewed_by', 'left') 
            ->where('reports.id', $id)
            ->get()->getRow();

        if (!$report) return redirect()->back()->with('error', 'Report not found.');

        $permissionSlug = "reports.{$report->reportable_type}s.view";
        if (!has_permission($permissionSlug) && !has_permission('reports.manage')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $adminId = session()->get('id');

        // ==========================================
        // 🔒 LOCK/CONCURRENCY CHECK LOGIC
        // ==========================================
        if ($report->status == 'pending') {
            
            // Check agar kisi aur ne lock kiya hua hai aur timeout nahi hua hai
            if (!empty($report->locked_by) && $report->locked_by != $adminId) {
                // Time check karo
                $lockTime = strtotime($report->locked_at);
                $timeout = strtotime('-15 minutes');
                
                if ($lockTime > $timeout) {
                    // Report is tightly locked by another admin
                    return redirect()->back()->with('error', "Report is currently being reviewed by Admin ID: {$report->locked_by}");
                }
            }
            
            // Agar free hai, toh current admin ke naam lock laga do
            $this->db->table('reports')
                ->where('id', $id)
                ->update([
                    'locked_by' => $adminId,
                    'locked_at' => date('Y-m-d H:i:s')
                ]);
        }
        // ==========================================

        $content = null;
        $accusedUser = null;
        $mediaType = 'post_image'; 
        $mediaPath = '';           

        // --- FETCH LOGIC ---

        if ($report->reportable_type == 'video') {
            $content = $this->db->table('videos')->where('id', $report->reportable_id)->get()->getRow();
            $mediaType = 'video'; 
            $mediaPath = $content->video_url ?? '';
        }
        elseif ($report->reportable_type == 'reel') {
            $content = $this->db->table('reels')->where('id', $report->reportable_id)->get()->getRow();
            $mediaType = 'reel';
            $mediaPath = $content->video_url ?? '';
        }
        elseif ($report->reportable_type == 'post') {
            $content = $this->db->table('posts')->where('id', $report->reportable_id)->get()->getRow();
            if ($content) {
                $mediaRow = $this->db->table('post_media')->where('post_id', $content->id)->orderBy('display_order', 'ASC')->limit(1)->get()->getRow();
                $mediaPath = $mediaRow->media_url ?? '';
                $mediaType = ($content->type == 'video') ? 'post_video' : 'post_image';
            }
        }
        elseif ($report->reportable_type == 'comment') {
            $content = $this->db->table('comments')->where('id', $report->reportable_id)->get()->getRow();
            $mediaType = 'text_only';
        }
        elseif ($report->reportable_type == 'user') {
            $accusedUser = $this->db->table('users')->select('id, username, name, email, avatar, bio, is_banned, created_at')->where('id', $report->reportable_id)->get()->getRow();
            $content = $accusedUser;
            $mediaType = 'profile_only';
        }
        // 🔥 CHANNEL LOGIC ADDED
        elseif ($report->reportable_type == 'channel') {
            $content = $this->db->table('channels')->where('id', $report->reportable_id)->get()->getRow();
            $mediaType = 'channel_cover'; 
            $mediaPath = $content->cover ?? ''; 
        }

        // Fetch Accused User (Common)
        if (!$accusedUser && $content && isset($content->user_id)) {
            $accusedUser = $this->db->table('users')->select('id, username, name, email, avatar, is_banned')->where('id', $content->user_id)->get()->getRow();
        }

        $folderName = $report->reportable_type . 's'; 
        
        return view("admin/reports/{$folderName}/view", [
            'report'      => $report,
            'content'     => $content,
            'accusedUser' => $accusedUser,
            'mediaType'   => $mediaType, 
            'mediaPath'   => $mediaPath  
        ]);
    }

    /**
     * 4. TAKE ACTION
     */
    public function take_action()
    {
        $request   = service('request');
        $session   = session();
        
        $reportId  = $request->getPost('report_id');
        
        $currentReport = $this->db->table('reports')->select('status')->where('id', $reportId)->get()->getRow();
        if (!$currentReport || $currentReport->status != 'pending') {
            return redirect()->back()->with('error', 'Action already taken on this report!');
        }

        $adminId = $session->get('id'); 
        if (!$adminId) return redirect()->to('admin/login')->with('error', 'Session expired.');

        $action    = $request->getPost('action'); 
        $type      = $request->getPost('type');
        $contentId = $request->getPost('content_id');
        $accusedId = $request->getPost('accused_user_id');
        $note      = $request->getPost('note'); 

        $reportUpdate = [
            'reviewed_by' => $adminId, 
            'reviewed_at' => date('Y-m-d H:i:s'),
            // ==========================================
            // 🔓 AUTO-UNLOCK AFTER ACTION
            // ==========================================
            'locked_by' => null,
            'locked_at' => null
        ];

        switch ($action) {
            case 'dismiss':
                if (!has_permission('reports.action')) return redirect()->back()->with('error', 'Permission Denied.');
                $reportUpdate['status'] = 'dismissed'; 
                log_action('DISMISS_REPORT', $reportId, 'report', $note);
                session()->setFlashdata('success', 'Report dismissed.');
                break;

            case 'delete_content':
                if (!has_permission('reports.action')) return redirect()->back()->with('error', 'Permission Denied.');
                
                if ($type == 'user') return redirect()->back()->with('error', 'Use Ban option for users.');

                $tableName = $type . 's'; 
                $this->db->table($tableName)->where('id', $contentId)->delete();
                
                $reportUpdate['status'] = 'resolved';
                
                $logMsg = "Deleted $type ID #$contentId via Report #$reportId. Reason: $note";
                log_action('DELETE_CONTENT', $contentId, $type, $logMsg);
                session()->setFlashdata('success', ucfirst($type) . ' deleted successfully.');
                break;

            case 'ban_user':
                if (!has_permission('users.ban')) return redirect()->back()->with('error', 'Permission Denied.');
                
                if ($accusedId) {
                    $this->db->table('users')->where('id', $accusedId)->update(['is_banned' => 1]); 
                    $reportUpdate['status'] = 'resolved';
                    
                    $logMsg = "Banned User ID #$accusedId via Report #$reportId. Reason: $note";
                    log_action('BAN_USER', $accusedId, 'user', $logMsg);
                    session()->setFlashdata('success', 'User has been banned.');
                }
                break;
                
            default:
                return redirect()->back()->with('error', 'Invalid Action');
        }

        $this->db->table('reports')->where('id', $reportId)->update($reportUpdate);
        return redirect()->to(base_url("admin/reports/{$type}s"));
    }

    /**
     * 5. DELETE REPORT LOG
     */
    public function delete($id)
    {
        if (!has_permission('reports.manage')) return redirect()->back()->with('error', 'Access Denied');
        log_action('DELETE_REPORT_LOG', $id, 'report', 'Admin deleted the report record manually.');
        $this->db->table('reports')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Report log removed.');
    }
}
