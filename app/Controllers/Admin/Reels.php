<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Reels extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Helpers for formatting, permissions, and admin logging
        helper(['format_helper', 'permission_helper', 'admin_logs_helper', 'text', 'url', 'number', 'media', 'date']);
    }

    /**
     * 📋 REELS LISTING: With Filters & Search
     */
    public function index()
    {
        if (!has_permission('reels.view')) return redirect()->to('admin/dashboard')->with('error', 'Access Denied');

        $builder = $this->db->table('reels r');
        // 🔥 FIX: Added u.name as full_name to resolve Undefined Property error
        $builder->select('r.*, u.username, u.name as full_name, c.name as channel_name');
        $builder->join('users u', 'u.id = r.user_id', 'left');
        $builder->join('channels c', 'c.id = r.channel_id', 'left');
        
        $search = $this->request->getGet('search');
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('r.caption', $search)
                    ->orLike('u.username', $search)
                    ->orLike('u.name', $search) 
                    ->orLike('c.name', $search)
                    ->groupEnd();
        }

        if ($status = $this->request->getGet('status')) $builder->where('r.status', $status);
        if ($visibility = $this->request->getGet('visibility')) $builder->where('r.visibility', $visibility);

        $data = [
            'title' => "Reels Management", 
            'reels' => $builder->orderBy('r.id', 'DESC')->get()->getResult()
        ];
        return view('admin/reels/index', $data);
    }

    /**
     * 👁️ VIEW REEL: Deep Analysis & Performance HUD
     */
    public function view($id)
    {
        if (!has_permission('reels.view')) return redirect()->to('admin/dashboard');

        $reel = $this->db->table('reels r')
            ->select('r.*, u.username, u.name as full_name, u.avatar as user_avatar, c.name as channel_name, c.id as channel_id') 
            ->join('users u', 'u.id = r.user_id', 'left')
            ->join('channels c', 'c.id = r.channel_id', 'left')
            ->where('r.id', $id)
            ->get()->getRow();

        if (!$reel) return redirect()->to('admin/reels')->with('error', 'Reel not found.');

        // 🏷️ Tags Mapping (Taggables from DB)
        $tagsData = $this->db->table('taggables t')
            ->select('h.tag')
            ->join('hashtags h', 'h.id = t.hashtag_id')
            ->where(['t.taggable_id' => $id, 't.taggable_type' => 'reel'])
            ->get()->getResult();
        $reel->tags = implode(', ', array_column($tagsData, 'tag'));

        // 📊 Advanced Stats Logic (Untouched as requested)
        $strikeCount = $this->db->table('channel_strikes')->where(['channel_id' => $reel->channel_id, 'status' => 'ACTIVE'])->countAllResults();
        $watchTime = $this->db->table('video_watch_sessions')->where(['video_id' => $id, 'video_type' => 'reel'])->selectSum('watch_duration')->get()->getRow()->watch_duration ?? 0;

        $views = max(1, $reel->views_count);
        $interactions = $reel->likes_count + $reel->comments_count + $reel->shares_count;
        $engagementRate = ($interactions / $views) * 100;

        // 🔥 Viral Score Mapping
        $rawViralScore = ($reel->shares_count * 25) + ($reel->comments_count * 15) + ($reel->likes_count * 5) + ($views * 0.1);
        $finalScore = ($reel->viral_score > 0) ? $reel->viral_score : $rawViralScore;

        $comments = $this->db->table('comments c')
            ->select('c.*, u.username, u.avatar')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where(['c.commentable_id' => $id, 'c.commentable_type' => 'reel'])
            ->orderBy('c.id', 'DESC')->limit(5)->get()->getResult();

        return view('admin/reels/view', [
            'reel'     => $reel,
            'comments' => $comments,
            'stats'    => [
                'watch_time_hrs'  => round($watchTime / 3600, 2),
                'engagement_rate' => round($engagementRate, 2),
                'viral_score'     => round($finalScore, 0),
                'viral_percent'   => min(100, round(($finalScore / 5000) * 100, 1)),
                'active_strikes'  => $strikeCount
            ]
        ]);
    }

    /**
     * ✏️ EDIT REEL: Metadata Preparation
     */
    public function edit($id)
    {
        if (!has_permission('reels.edit')) return redirect()->back();
        
        $reel = $this->db->table('reels')->where('id', $id)->get()->getRow();
        if (!$reel) return redirect()->to('admin/reels');

        // 🔥 Added: Fetch and map tags for the edit form
        $tagsData = $this->db->table('taggables t')
            ->select('h.tag')
            ->join('hashtags h', 'h.id = t.hashtag_id')
            ->where(['t.taggable_id' => $id, 't.taggable_type' => 'reel'])
            ->get()->getResult();
        $reel->tags = implode(', ', array_column($tagsData, 'tag'));

        $data = ['title' => "Refine Reel Map", 'reel' => $reel];
        return view('admin/reels/edit', $data);
    }

    /**
     * 🔄 UPDATE REEL: Apply Changes & Audit Log
     */
    public function update($id)
    {
        if (!has_permission('reels.edit')) return redirect()->back();
        
        $updateData = [
            'caption'              => esc($this->request->getPost('caption')),
            'status'               => $this->request->getPost('status'),
            'visibility'           => $this->request->getPost('visibility'),
            'monetization_enabled' => $this->request->getPost('monetization_enabled') ? 1 : 0,
            'updated_at'           => date('Y-m-d H:i:s')
        ];

        if ($this->db->table('reels')->where('id', $id)->update($updateData)) {
            log_action('UPDATE_REEL', $id, 'reel', "Refined Reel ID: #$id");
        }
        
        return redirect()->to(base_url('admin/reels/view/'.$id))->with('success', 'Reel Map Synchronized!');
    }

    /**
     * 🗑️ DELETE REEL: Full Cleanup Logic
     */
    public function delete($id)
    {
        if (!has_permission('reels.delete')) return redirect()->back();
        
        $reel = $this->db->table('reels')->where('id', $id)->get()->getRow();
        if (!$reel) return redirect()->back();

        $this->db->transStart();
        $this->db->table('taggables')->where(['taggable_id' => $id, 'taggable_type' => 'reel'])->delete();
        $this->db->table('video_watch_sessions')->where(['video_id' => $id, 'video_type' => 'reel'])->delete();
        $this->db->table('reels')->where('id', $id)->delete();
        $this->db->transComplete();

        if ($this->db->transStatus() === TRUE) {
            log_action('DELETE_REEL', $id, 'reel', "Permanently wiped Reel: " . ($reel->caption ?? 'ID:'.$id));
            return redirect()->to(base_url('admin/reels'))->with('success', 'Reel and associated meta wiped!');
        }
        return redirect()->back()->with('error', 'Cleanup failure.');
    }

    /**
     * 💬 COMMENT MODERATION
     */
    public function delete_comment($id)
    {
        if (!has_permission('reels.edit')) return redirect()->back();
        
        $comment = $this->db->table('comments')->where('id', $id)->get()->getRow();
        if ($comment) {
            $this->db->table('comments')->where('id', $id)->delete();
            log_action('DELETE_REEL_COMMENT', $id, 'comment', "Removed comment on Reel from Admin HUD.");
        }

        return redirect()->back()->with('success', 'Comment purged.');
    }
}

