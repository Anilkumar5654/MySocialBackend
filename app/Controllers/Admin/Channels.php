<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Channels extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Sabhi zaroori helpers load kiye
        helper(['format_helper', 'permission_helper', 'admin_logs_helper', 'url', 'media', 'text', 'form']);
    }

    // =========================================================================
    // 📺 SECTION 1: BASIC CHANNEL CRUD
    // =========================================================================

    public function index()
    {
        if (!has_permission('channels.view')) return redirect()->to('admin/dashboard')->with('error', 'Access Denied');

        $search = $this->request->getGet('search');
        $builder = $this->db->table('channels c');
        $builder->select('c.*, u.username, u.name as owner_name');
        $builder->join('users u', 'u.id = c.user_id');

        if ($search) {
            $builder->groupStart()
                ->like('c.name', $search) 
                ->orLike('c.handle', $search)
                ->orLike('u.username', $search)
                ->groupEnd();
        }

        $data = [
            'title'    => 'Channels Directory',
            'channels' => $builder->orderBy('c.id', 'DESC')->get()->getResult()
        ];
        return view('admin/channels/index', $data);
    }

    public function view($id)
    {
        if (!has_permission('channels.view')) return redirect()->back();

        $channel = $this->db->table('channels c')
            ->select('c.*, u.username, u.kyc_status, u.followers_count') // ✅ Followers added
            ->join('users u', 'u.id = c.user_id')
            ->where('c.id', $id)->get()->getRow();

        if (!$channel) return redirect()->to('admin/channels')->with('error', 'Channel not found.');

        $activeStrikes = $this->db->table('channel_strikes')
            ->where(['channel_id' => $id, 'status' => 'ACTIVE', 'type' => 'STRIKE'])
            ->countAllResults();

        $strikes = $this->db->table('channel_strikes')
            ->where('channel_id', $id)
            ->orderBy('created_at', 'DESC')
            ->get()->getResult();

        $data = [
            'title'          => 'Channel Details',
            'channel'        => $channel,
            'active_strikes' => $activeStrikes,
            'strikes'        => $strikes
        ];
        return view('admin/channels/view', $data);
    }

    public function edit($id)
    {
        if (!has_permission('channels.edit')) return redirect()->back()->with('error', 'Access Denied');
        
        $channel = $this->db->table('channels')->where('id', $id)->get()->getRow();
        if (!$channel) return redirect()->to('admin/channels')->with('error', 'Channel not found.');

        $data = [
            'title'   => 'Edit Channel: ' . $channel->name,
            'channel' => $channel
        ];
        return view('admin/channels/edit', $data);
    }

    public function update($id)
    {
        if (!has_permission('channels.edit')) return redirect()->back();

        $mStatus = $this->request->getPost('monetization_status');
        
        $postData = [
            'name'                    => esc($this->request->getPost('channel_name')),
            'handle'                  => esc($this->request->getPost('handle')),
            'description'             => esc($this->request->getPost('description')),
            'about_text'              => esc($this->request->getPost('about_text')),
            'trust_score'             => (int)$this->request->getPost('trust_score'),
            'is_verified'             => $this->request->getPost('is_verified') ? 1 : 0,
            'creator_level'           => $this->request->getPost('creator_level'),
            'monetization_status'     => $mStatus,
            'monetization_reason'     => esc($this->request->getPost('monetization_reason')),
            'is_monetization_enabled' => ($mStatus === 'APPROVED') ? 1 : 0
        ];

        if ($this->db->table('channels')->where('id', $id)->update($postData)) {
            log_action('UPDATE_CHANNEL', $id, 'channels', "Channel settings updated for @{$postData['handle']}");
        }
        
        return redirect()->to('admin/channels/view/'.$id)->with('success', 'Changes saved successfully.');
    }

    public function delete($id)
    {
        if (!has_permission('channels.delete')) return redirect()->back()->with('error', 'Access Denied');
        
        $channel = $this->db->table('channels')->where('id', $id)->get()->getRow();
        if (!$channel) return redirect()->back()->with('error', 'Channel not found.');

        $this->db->transStart();
        // Delete related strikes first
        $this->db->table('channel_strikes')->where('channel_id', $id)->delete();
        // Delete channel
        $this->db->table('channels')->where('id', $id)->delete();
        $this->db->transComplete();

        if ($this->db->transStatus() === TRUE) {
            log_action('DELETE_CHANNEL', $id, 'channels', "Permanently removed channel: @{$channel->handle}");
            return redirect()->to('admin/channels')->with('success', 'Channel and related data removed.');
        }

        return redirect()->back()->with('error', 'Deletion failed.');
    }

    // =========================================================================
    // 💰 SECTION 2: MONETIZATION REVIEW SYSTEM
    // =========================================================================

    public function monetization_requests()
    {
        if (!has_permission('channels.view')) return redirect()->back();

        $builder = $this->db->table('channels c');
        $builder->select('c.*, u.username, u.kyc_status');
        $builder->join('users u', 'u.id = c.user_id');
        $builder->where('c.monetization_status', 'PENDING');

        $data = [
            'title'    => 'Monetization Queue',
            'requests' => $builder->orderBy('c.monetization_applied_date', 'ASC')->get()->getResult()
        ];
        return view('admin/channels/monetization/index', $data);
    }

    public function monetization_view($id)
    {
        if (!has_permission('channels.view')) return redirect()->back();

        $channel = $this->db->table('channels c')
            ->select('c.*, u.username, u.kyc_status, u.followers_count') // ✅ Followers added here
            ->join('users u', 'u.id = c.user_id')
            ->where('c.id', $id)->get()->getRow();

        if (!$channel) return redirect()->to('admin/channels/monetization')->with('error', 'Request not found.');

        $activeStrikes = $this->db->table('channel_strikes')
            ->where(['channel_id' => $id, 'status' => 'ACTIVE', 'type' => 'STRIKE'])
            ->countAllResults();

        $data = [
            'title'          => 'Review Monetization: ' . $channel->name,
            'channel'        => $channel,
            'active_strikes' => $activeStrikes
        ];
        return view('admin/channels/monetization/view', $data);
    }

    public function monetization_process()
    {
        if (!has_permission('channels.edit')) return redirect()->back();

        $id = $this->request->getPost('channel_id');
        $action = $this->request->getPost('action'); 
        $reason = esc($this->request->getPost('reason'));
        
        $status = ($action === 'approve') ? 'APPROVED' : 'REJECTED';
        $update = [
            'monetization_status'     => $status,
            'is_monetization_enabled' => ($action === 'approve') ? 1 : 0,
            'monetization_reason'     => $reason,
            'updated_at'              => date('Y-m-d H:i:s')
        ];

        if ($this->db->table('channels')->where('id', $id)->update($update)) {
            log_action('MONETIZATION_DECISION', $id, 'channels', "Application $status. Reason: $reason");
        }
        
        return redirect()->to('admin/channels/monetization')->with('success', "Application $status successfully.");
    }

    public function monetization_toggle_status($id)
    {
        if (!has_permission('channels.edit')) return $this->response->setJSON(['status' => 'error', 'message' => 'No Permission']);

        $channel = $this->db->table('channels')->where('id', $id)->get()->getRow();
        if (!$channel) return $this->response->setJSON(['status' => 'error', 'message' => 'Channel not found']);

        $newStatus = ($channel->monetization_status === 'APPROVED') ? 'SUSPENDED' : 'APPROVED';

        $this->db->table('channels')->where('id', $id)->update([
            'monetization_status'     => $newStatus,
            'is_monetization_enabled' => ($newStatus === 'APPROVED') ? 1 : 0
        ]);

        log_action('MONETIZATION_TOGGLE', $id, 'channels', "Status toggled to $newStatus");

        return $this->response->setJSON([
            'status' => 'success', 
            'message' => 'Monetization status: ' . $newStatus, 
            'token' => csrf_hash()
        ]);
    }

    // =========================================================================
    // ⚡ SECTION 3: AJAX & STRIKE SYSTEM
    // =========================================================================

    public function issue_strike()
    {
        if (!has_permission('channels.edit')) return $this->response->setJSON(['status' => 'error', 'message' => 'No Permission']);

        $id = $this->request->getPost('channel_id');
        $type = $this->request->getPost('type'); 
        $reason = esc($this->request->getPost('reason'));
        
        $this->db->transStart();

        $this->db->table('channel_strikes')->insert([
            'channel_id' => $id,
            'reason' => $reason,
            'description' => esc($this->request->getPost('description')),
            'type' => $type,
            'status' => 'ACTIVE',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days'))
        ]);

        $channel = $this->db->table('channels')->where('id', $id)->get()->getRow();
        $penalty = ($type === 'STRIKE') ? 20 : 5;
        $newScore = max(0, $channel->trust_score - $penalty);
        
        $update = ['trust_score' => $newScore];

        if ($type === 'STRIKE') {
            $activeCount = $this->db->table('channel_strikes')
                ->where(['channel_id' => $id, 'status' => 'ACTIVE', 'type' => 'STRIKE'])->countAllResults();
            
            if ($activeCount >= 3) {
                $update['monetization_status'] = 'SUSPENDED';
                $update['is_monetization_enabled'] = 0;
            }
        }

        $this->db->table('channels')->where('id', $id)->update($update);
        log_action('ISSUE_PENALTY', $id, 'channels', "$type issued. Trust Score: $newScore");
        $this->db->transComplete();

        return $this->response->setJSON([
            'status' => 'success', 
            'message' => 'Penalty Recorded',
            'token' => csrf_hash() 
        ]);
    }
}
