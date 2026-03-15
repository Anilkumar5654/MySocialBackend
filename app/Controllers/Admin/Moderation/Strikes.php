<?php

namespace App\Controllers\Admin\Moderation;

use App\Controllers\BaseController;
use Throwable;

class Strikes extends BaseController
{
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        // Sabhi helpers loaded hain
        helper(['admin/strike', 'text', 'date', 'admin_helper']); 
    }

    /**
     * 📜 1. AUDIT HISTORY (Global Stats Synced)
     */
    public function index()
    {
        if (!has_permission('strikes.view') && session()->get('role_id') != 1) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $status = $this->request->getGet('status') ?? 'ALL';

        $stats = [
            'active_strikes' => $this->db->table('channel_strikes')->where(['type' => 'STRIKE', 'status' => 'ACTIVE'])->countAllResults(),
            'total_claims'   => $this->db->table('channel_strikes')->where('type', 'CLAIM')->countAllResults(),
            'total_records'  => $this->db->table('channel_strikes')->countAllResults()
        ];

        $builder = $this->db->table('channel_strikes as s')
            ->select('s.*, offender.name as channel_name, offender.handle as offender_handle, offender.avatar as offender_avatar, reporter.name as reporter_name, reporter.handle as reporter_handle')
            ->join('channels as offender', 'offender.id = s.channel_id', 'left')
            ->join('channels as reporter', 'reporter.id = s.reporter_channel_id', 'left')
            ->orderBy('s.created_at', 'DESC');

        if ($status === 'ACTIVE') {
            $builder->where('s.status', 'ACTIVE');
        } elseif ($status === 'CLAIM') {
            $builder->where('s.type', 'CLAIM');
        } elseif ($status === 'EXPIRED') {
            $builder->where('s.status', 'EXPIRED');
        }

        $strikes = $builder->get()->getResult();
        foreach ($strikes as $s) { 
            $s->content_title = $this->getContentTitle($s->content_type, $s->content_id); 
        }

        return view('admin/moderation/strikes/index', [
            'title'   => 'Copyright History',
            'strikes' => $strikes,
            'stats'   => $stats,
            'filter'  => $status
        ]);
    }

    /**
     * 📥 2. PENDING REVIEW (User Reports Queue)
     */
    public function claims()
    {
        $this->autoUnlockCases('PENDING_REVIEW');

        $claims = $this->db->table('channel_strikes as s')
            ->select('s.*, offender.name as channel_name, offender.handle as offender_handle, reporter.name as reporter_name, reporter.handle as reporter_handle')
            ->join('channels as offender', 'offender.id = s.channel_id', 'left')
            ->join('channels as reporter', 'reporter.id = s.reporter_channel_id', 'left')
            ->where('s.type', 'PENDING_REVIEW') 
            ->where('s.status', 'ACTIVE')
            ->orderBy('s.created_at', 'ASC') 
            ->get()->getResult();

        foreach ($claims as $c) { 
            $c->content_title = $this->getContentTitle($c->content_type, $c->content_id); 
        }

        return view('admin/moderation/strikes/claims', ['claims' => $claims]);
    }

    /**
     * 🚀 3. PENDING APPEALS (Creators Defense)
     */
    public function appeals() 
    {
        $this->autoUnlockCases('APPEAL');

        $appeals = $this->db->table('channel_strikes as s')
            ->select('s.*, offender.name as channel_name, offender.handle as offender_handle, offender.avatar as offender_avatar, reporter.name as reporter_name')
            ->join('channels as offender', 'offender.id = s.channel_id', 'left')
            ->join('channels as reporter', 'reporter.id = s.reporter_channel_id', 'left')
            ->where('s.appeal_status', 'PENDING') 
            ->where('s.status', 'ACTIVE')
            ->orderBy('s.appealed_at', 'DESC')
            ->get()->getResult();

        foreach ($appeals as $a) { 
            $a->content_title = $this->getContentTitle($a->content_type, $a->content_id); 
        }

        return view('admin/moderation/strikes/appeals', ['appeals' => $appeals]);
    }

    /**
     * ⚖️ 4. REPORT DECISION (ISSUE STRIKE / CLAIM / REJECT / WARNING)
     */
    public function report_decision()
    {
        $requestId  = $this->request->getPost('claim_id');
        $decision   = $this->request->getPost('decision'); 
        $originalId = $this->request->getPost('original_video_id') ?: null; 
        $points     = (int)($this->request->getPost('severity_points') ?? 0);

        if (empty($requestId)) {
            $request = (object)[
                'channel_id'   => $this->request->getPost('channel_id'),
                'content_type' => $this->request->getPost('content_type'),
                'content_id'   => $this->request->getPost('content_id'),
                'reporter_channel_id' => null
            ];
        } else {
            $request = $this->db->table('channel_strikes')->where('id', $requestId)->get()->getRow();
            if (!$request) return redirect()->back()->with('error', 'Request not found');
        }

        try {
            $this->db->transStart();

            // Handle REJECT
            if ($decision === 'REJECT') {
                $fakePenalty = (int)get_strike_setting_value('trust_penalty_fake_report', 3);
                if ($fakePenalty > 0 && !empty($request->reporter_channel_id)) {
                    $this->db->query("UPDATE channels SET trust_score = GREATEST(0, CAST(trust_score AS SIGNED) - ?) WHERE id = ?", [$fakePenalty, $request->reporter_channel_id]);
                }
                $this->db->table('channel_strikes')->where('id', $requestId)->update(['status' => 'REJECTED', 'locked_by' => null, 'locked_at' => null]);
                $this->db->transComplete(); 
                return redirect()->back()->with('success', "Report Rejected.");
            }

            // Type Mapping
            $type = ($decision === 'SEND_WARNING') ? 'WARNING' : (($decision === 'REVENUE_CLAIM') ? 'CLAIM' : 'STRIKE');
            if ($type !== 'STRIKE') $points = 0;

            // Blacklist (Hash) Logic
            if (!empty($originalId)) {
                $targetTable = (strtoupper($request->content_type) === 'VIDEO') ? 'videos' : 'reels';
                $offending = $this->db->table($targetTable)->select('video_hash')->where('id', $request->content_id)->get()->getRow();
                if ($offending && !empty($offending->video_hash)) {
                    $exists = $this->db->table('copyright_blacklist')->where('banned_hash', $offending->video_hash)->countAllResults();
                    if ($exists == 0) {
                        $this->db->table('copyright_blacklist')->insert([
                            'banned_hash'       => $offending->video_hash,
                            'original_video_id' => $originalId,
                            'reason'            => 'Manual Action: ' . $decision,
                            'created_at'        => date('Y-m-d H:i:s')
                        ]);
                    }
                }
            }

            // Revenue Share Processing
            if ($decision === 'REVENUE_CLAIM' && !empty($originalId)) {
                $orig = $this->db->table('videos')->where('id', $originalId)->get()->getRow();
                $uploader = $this->db->table('videos')->where('id', $request->content_id)->get()->getRow();
                if ($orig && $uploader) {
                    $this->db->table('revenue_shares')->insert([
                        'claimed_content_id' => $request->content_id,
                        'content_type'       => strtoupper($request->content_type),
                        'original_creator_id'=> $orig->user_id,
                        'uploader_id'        => $uploader->user_id,
                        'status'             => 'ACTIVE',
                        'created_at'         => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Execute Logic
            $data = [
                'channel_id'   => $request->channel_id,
                'content_type' => strtoupper($request->content_type),
                'content_id'   => $request->content_id,
                'type'         => $type,
                'reason'       => $this->request->getPost('reason') ?: 'Policy Violation',
                'description'  => $this->request->getPost('description') ?: "Decision: " . $decision,
                'severity_points' => $points,
                'original_content_id' => $originalId
            ];
            
            execute_strike_logic($data, $requestId);
            
            $this->db->transComplete();
            return redirect()->to('admin/moderation/strikes')->with('success', "Decision applied.");

        } catch (Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * ✅ 5. APPEAL ACTION
     */
    public function appeal_action()
    {
        $id     = $this->request->getPost('id');
        $status = $this->request->getPost('status'); 
        try {
            if ($status == 'APPROVED') {
                revert_strike_logic($id, 'APPEAL_APPROVED');
                $this->db->table('channel_strikes')->where('id', $id)->update(['appeal_status' => 'APPROVED', 'locked_by' => null, 'locked_at' => null]);
            } else {
                $this->db->table('channel_strikes')->where('id', $id)->update(['appeal_status' => 'REJECTED', 'status' => 'REJECTED', 'locked_by' => null, 'locked_at' => null]);
            }
            return redirect()->to('admin/moderation/strikes/appeals')->with('success', "Appeal processed.");
        } catch (Throwable $e) { 
            return redirect()->back()->with('error', $e->getMessage()); 
        }
    }

    /**
     * 🗑️ 6. MANUAL REMOVE
     */
    public function remove_manual($id)
    {
        try {
            revert_strike_logic($id, 'REMOVED_BY_ADMIN'); 
            return redirect()->back()->with('success', "Action revoked.");
        } catch (Throwable $e) { 
            return redirect()->back()->with('error', $e->getMessage()); 
        }
    }

    /**
     * 🔍 7. VIEW CASE (UPGRADED WITH SIDE-BY-SIDE ANALYTICS)
     */
    public function view($id)
    {
        // 1. Fetch Strike & Channel Details
        $strike = $this->db->table('channel_strikes as s')
            ->select('s.*, offender.name as channel_name, offender.handle as offender_handle, offender.avatar as offender_avatar, offender.strikes_count, reporter.name as reporter_name, reporter.handle as reporter_handle')
            ->join('channels as offender', 'offender.id = s.channel_id', 'left')
            ->join('channels as reporter', 'reporter.id = s.reporter_channel_id', 'left')
            ->where('s.id', $id)->get()->getRow();

        if (!$strike) return redirect()->to('admin/moderation/strikes')->with('error', 'Record not found.');

        // Admin Locking logic
        $adminId = session()->get('id');
        $this->db->table('channel_strikes')->where('id', $id)->update(['locked_by' => $adminId, 'locked_at' => date('Y-m-d H:i:s')]);

        $targetTable = (strtoupper($strike->content_type) === 'REEL') ? 'reels' : 'videos';

        // 🎯 OFFENDER VIDEO DETAILS (Upload Date + Views)
        $offenderMedia = $this->db->table($targetTable)
            ->select('video_url, thumbnail_url, created_at as upload_date, views_count')
            ->where('id', $strike->content_id)->get()->getRow();
        
        $strike->target_video_url = $offenderMedia->video_url ?? '';
        $strike->target_thumbnail = $offenderMedia->thumbnail_url ?? '';
        $strike->offender_upload_date = $offenderMedia->upload_date ?? null;
        $strike->offender_views = $offenderMedia->views_count ?? 0;

        // 🎯 ORIGINAL VIDEO DETAILS (Join with Channels to get Owner Name)
        $strike->original_video_url = null;
        $strike->original_upload_date = null;
        $strike->original_owner_name = 'External/Evidence Link';
        $strike->original_views = 0;

        if (!empty($strike->original_content_id)) {
            $orig = $this->db->table('videos as v')
                ->select('v.video_url, v.thumbnail_url, v.created_at as upload_date, v.views_count, c.name as owner_name')
                ->join('channels as c', 'c.user_id = v.user_id', 'left')
                ->where('v.id', $strike->original_content_id)
                ->get()->getRow();
            
            if ($orig) {
                $strike->original_video_url = $orig->video_url;
                $strike->original_thumbnail = $orig->thumbnail_url;
                $strike->original_upload_date = $orig->upload_date;
                $strike->original_views = $orig->views_count ?? 0;
                $strike->original_owner_name = $orig->owner_name ?? 'System User';
            }
        }

        $strike->content_title = $this->getContentTitle($strike->content_type, $strike->content_id);
        return view('admin/moderation/strikes/view', ['title' => 'Case Details', 'strike' => $strike]);
    }

    /**
     * 🛡️ INTERNAL HELPERS
     */
    private function autoUnlockCases($mode) {
        $timeout = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $builder = $this->db->table('channel_strikes')->where('locked_at <', $timeout);
        if ($mode === 'PENDING_REVIEW') $builder->where('type', 'PENDING_REVIEW');
        else $builder->where('appeal_status', 'PENDING');
        $builder->update(['locked_by' => null, 'locked_at' => null]);
    }

    private function getContentTitle($type, $id) {
        if (!$id) return 'System';
        $table = ($type == 'VIDEO') ? 'videos' : 'reels';
        $col = ($type == 'VIDEO') ? 'title' : 'caption'; 
        $row = $this->db->table($table)->select($col)->where('id', $id)->get()->getRow();
        return $row ? $row->$col : 'Deleted Content';
    }
}
