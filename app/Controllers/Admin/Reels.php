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
     * 👁️ VIEW REEL: Deep Analysis & Performance HUD (Ultra Upgraded)
     */
    public function view($id)
    {
        if (!has_permission('reels.view')) return redirect()->to('admin/dashboard');

        // [SECTION 1] Core Reel, Creator, and Channel Info (Upgraded for Deep Dive)
        $reel = $this->db->table('reels r')
            ->select('r.*, u.username, u.name as full_name, u.avatar as user_avatar, u.is_verified as user_verified, u.followers_count,
                      c.name as channel_name, c.id as channel_id, c.unique_id as channel_unique_id, c.videos_count as channel_videos_count,
                      c.strikes_count, c.warnings_count, IFNULL(c.trust_score, 100) as trust_score, 
                      ov.video_url as original_video_url, ov.thumbnail_url as original_thumbnail, ou.name as original_owner_name, 
                      ov.created_at as original_created_at') 
            ->join('users u', 'u.id = r.user_id', 'left')
            ->join('channels c', 'c.id = r.channel_id', 'left')
            ->join('reels ov', 'ov.id = r.original_content_id', 'left')
            ->join('users ou', 'ou.id = ov.user_id', 'left')
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

        // [SECTION 2] Reports & Strikes
        $reports = $this->db->table('reports r')
            ->select('r.*, u.username as reporter_name')
            ->join('users u', 'u.id = r.reporter_id', 'left')
            ->where(['reportable_id' => $id, 'reportable_type' => 'reel'])
            ->orderBy('r.id', 'DESC')->get()->getResult();

        $reel_strike = $this->db->table('channel_strikes')
            ->where(['content_id' => $id, 'content_type' => 'REEL', 'status' => 'ACTIVE'])
            ->orderBy('id', 'DESC')->get()->getRow();

        // 📊 Original Advanced Stats Logic (Preserved)
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

        // [SECTION 3] Monetization & Payouts (Real Data Integration)
        $rev_share = $this->db->table('revenue_shares rs')
            ->select('rs.*, u.username as claimant_username')
            ->join('users u', 'u.id = rs.original_creator_id', 'left')
            ->where(['rs.claimed_content_id' => $id, 'rs.content_type' => 'REEL', 'rs.status' => 'ACTIVE'])
            ->get()->getRow();

        $creator_earnings = $this->db->table('creator_earnings')
            ->select('SUM(amount) as total_earnings')
            ->where(['content_id' => $id, 'content_type' => 'reel'])
            ->get()->getRow();
            
        $total_earnings = $creator_earnings->total_earnings ?? 0;

        $total_imps = $this->db->tableExists('ad_impressions') 
            ? $this->db->table('ad_impressions')->where(['content_id' => $id, 'content_type' => 'reel'])->countAllResults()
            : 0;

        $avg_rpm = ($total_imps > 0) ? ($total_earnings / $total_imps) * 1000 : 0;
        $ad_stats = (object)[
            'total_imps' => $total_imps,
            'avg_rpm'    => number_format($avg_rpm, 2)
        ];

        // [SECTION 4] Analytics & Watch Trends (Last 7 Days)
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $recent_views = $this->db->table('views')->where(['viewable_id' => $id, 'viewable_type' => 'reel', 'created_at >=' => $seven_days_ago])->countAllResults();
        $recent_likes = $this->db->table('likes')->where(['likeable_id' => $id, 'likeable_type' => 'reel', 'created_at >=' => $seven_days_ago])->countAllResults();
        $recent_comments = $this->db->table('comments')->where(['commentable_id' => $id, 'commentable_type' => 'reel', 'created_at >=' => $seven_days_ago])->countAllResults();

        // Graph Data (Views by Day)
        $graph_data = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(id) as count 
            FROM views 
            WHERE viewable_id = ? AND viewable_type = 'reel' AND created_at >= ?
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ", [$id, $seven_days_ago])->getResult();

        // [SECTION 5] Audience Insights (Geo, Age, Device)
        $geo_stats = $this->db->query("
            SELECT u.country, COUNT(v.id) as count 
            FROM views v 
            JOIN users u ON u.id = v.user_id 
            WHERE v.viewable_id = ? AND v.viewable_type = 'reel' 
            GROUP BY u.country 
            ORDER BY count DESC LIMIT 5
        ", [$id])->getResult();

        $age_stats = $this->db->query("
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) BETWEEN 13 AND 17 THEN '13-17'
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) BETWEEN 18 AND 24 THEN '18-24'
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) BETWEEN 25 AND 34 THEN '25-34'
                    WHEN TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) BETWEEN 35 AND 44 THEN '35-44'
                    ELSE '45+' 
                END as age_group,
                COUNT(v.id) as count
            FROM views v 
            JOIN users u ON u.id = v.user_id 
            WHERE v.viewable_id = ? AND v.viewable_type = 'reel' AND u.dob IS NOT NULL
            GROUP BY age_group
        ", [$id])->getResult();

        $device_stats = $this->db->query("
            SELECT a.device_type, COUNT(v.id) as count 
            FROM views v 
            JOIN auth_tokens a ON a.user_id = v.user_id 
            WHERE v.viewable_id = ? AND v.viewable_type = 'reel' AND a.device_type IS NOT NULL
            GROUP BY a.device_type
        ", [$id])->getResult();

        $process_log = $this->db->tableExists('video_processing_queue') ? $this->db->table('video_processing_queue')->where(['video_id' => $id, 'video_type' => 'reel'])->orderBy('id', 'DESC')->get()->getRow() : null;

        // Merge original stats with new deep dive metrics
        $stats = [
            'watch_time_hrs'  => round($watchTime / 3600, 2),
            'avg_watch_dur'   => gmdate("i:s", (int)($watchTime / max(1, $views))),
            'avg_completion'  => min(100, round(($watchTime / max(1, $views)) / max(1, $reel->duration) * 100, 1)),
            'engagement_rate' => round($engagementRate, 2),
            'viral_score'     => round($finalScore, 0),
            'viral_percent'   => min(100, round(($finalScore / 5000) * 100, 1)),
            'active_strikes'  => $strikeCount,
            'recent_views'    => $recent_views,
            'recent_likes'    => $recent_likes,
            'recent_comments' => $recent_comments
        ];

        return view('admin/reels/view', [
            'reel'             => $reel,
            'video'            => $reel, // Passing as $video too to keep View UI code compatible
            'video_strike'     => $reel_strike,
            'comments'         => $comments,
            'reports'          => $reports,
            'ad_stats'         => $ad_stats,
            'creator_earnings' => $creator_earnings,
            'rev_share'        => $rev_share,
            'process_log'      => $process_log,
            'stats'            => $stats,
            'graph_data'       => $graph_data,
            'geo_stats'        => $geo_stats,
            'age_stats'        => $age_stats,
            'device_stats'     => $device_stats,
            'title'            => 'Reel Deep Dive Intelligence'
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
     * 🗑️ DELETE REEL: Full Cleanup Logic (Upgraded for Deep Dive Tables)
     */
    public function delete($id)
    {
        if (!has_permission('reels.delete')) return redirect()->back();
        
        $reel = $this->db->table('reels')->where('id', $id)->get()->getRow();
        if (!$reel) return redirect()->back();

        $this->db->transStart();
        
        // Wipe all dependencies dynamically
        $this->db->table('taggables')->where(['taggable_id' => $id, 'taggable_type' => 'reel'])->delete();
        $this->db->table('video_watch_sessions')->where(['video_id' => $id, 'video_type' => 'reel'])->delete();
        
        // Deep Dive cleanup additions
        if ($this->db->tableExists('views')) $this->db->table('views')->where(['viewable_id' => $id, 'viewable_type' => 'reel'])->delete();
        if ($this->db->tableExists('likes')) $this->db->table('likes')->where(['likeable_id' => $id, 'likeable_type' => 'reel'])->delete();
        if ($this->db->tableExists('comments')) $this->db->table('comments')->where(['commentable_id' => $id, 'commentable_type' => 'reel'])->delete();
        if ($this->db->tableExists('reports')) $this->db->table('reports')->where(['reportable_id' => $id, 'reportable_type' => 'reel'])->delete();
        if ($this->db->tableExists('ad_impressions')) $this->db->table('ad_impressions')->where(['content_id' => $id, 'content_type' => 'reel'])->delete();
        if ($this->db->tableExists('ad_clicks')) $this->db->table('ad_clicks')->where(['content_id' => $id, 'content_type' => 'reel'])->delete();
        if ($this->db->tableExists('channel_strikes')) $this->db->table('channel_strikes')->where(['content_id' => $id, 'content_type' => 'REEL'])->delete();

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
