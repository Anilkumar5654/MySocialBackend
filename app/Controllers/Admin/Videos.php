<?php 

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Throwable;

class Videos extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Saare helpers preserve kiye gaye hain
        helper(['format_helper', 'permission_helper', 'admin_logs_helper', 'text', 'url', 'number', 'media', 'date']);
    }

    /**
     * 1. 📋 VIDEO LISTING (100% Preserved)
     */
    public function index()
    {
        if (!has_permission('videos.view')) return redirect()->to('admin/dashboard')->with('error', 'Access Denied');

        $builder = $this->db->table('videos v');
        $builder->select('v.*, u.username, c.name as channel_name, c.handle as channel_handle');
        $builder->join('users u', 'u.id = v.user_id', 'left');
        $builder->join('channels c', 'c.id = v.channel_id', 'left');
        
        $search = $this->request->getGet('search');
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('v.title', $search)
                    ->orLike('u.username', $search)
                    ->orLike('c.name', $search)
                    ->groupEnd();
        }

        if ($status = $this->request->getGet('status')) $builder->where('v.status', $status);
        if ($visibility = $this->request->getGet('visibility')) $builder->where('v.visibility', $visibility);

        $data = [
            'title'  => "Video Content Library", 
            'videos' => $builder->orderBy('v.id', 'DESC')->get()->getResult()
        ];

        return view('admin/videos/index', $data);
    }

    /**
     * 2. 👁️ VIEW VIDEO DETAILS (Ultra Upgrade - 100% Live Data Sync)
     */
    public function view($id)
    {
        if (!has_permission('videos.view')) return redirect()->to('admin/dashboard');

        // [SECTION A & B] Full Sync: Video + Channel Health + User Info
        $video = $this->db->table('videos v')
            ->select('v.*, u.username, u.name as full_name, u.avatar as user_avatar, u.is_verified as user_verified, 
                      c.name as channel_name, c.id as channel_id, c.strikes_count, IFNULL(c.trust_score, 100) as trust_score, 
                      ov.video_url as original_video_url, ov.thumbnail_url as original_thumbnail, ou.name as original_owner_name, 
                      ov.created_at as original_created_at')
            ->join('users u', 'u.id = v.user_id', 'left')
            ->join('channels c', 'c.id = v.channel_id', 'left')
            ->join('videos ov', 'ov.id = v.original_content_id', 'left')
            ->join('users ou', 'ou.id = ov.user_id', 'left')
            ->where('v.id', $id)
            ->get()->getRow();

        if (!$video) return redirect()->to('admin/videos')->with('error', 'Video not found.');

        // 🏷️ Preserving Tags Mapping Logic
        $tagsData = $this->db->table('taggables t')->select('h.tag')->join('hashtags h', 'h.id = t.hashtag_id')->where(['t.taggable_id' => $id, 't.taggable_type' => 'video'])->get()->getResult();
        $video->tags = implode(', ', array_column($tagsData, 'tag'));

        // [SECTION B] Deep Moderation: Report History
        $reports = $this->db->table('reports r')->select('r.*, u.username as reporter_name')->join('users u', 'u.id = r.reporter_id', 'left')->where(['reportable_id' => $id, 'reportable_type' => 'video'])->orderBy('r.id', 'DESC')->get()->getResult();

        // 🔥 Fetch Active Strike/Warning for Dynamic Banner
        $video_strike = $this->db->table('channel_strikes')
            ->where(['content_id' => $id, 'content_type' => 'VIDEO', 'status' => 'ACTIVE'])
            ->orderBy('id', 'DESC')->get()->getRow();

        // 🔥 Revenue Share with real claimant details (joined with users)
        $rev_share = $this->db->table('revenue_shares rs')
            ->select('rs.*, u.name as claimant_name, u.username as claimant_username')
            ->join('users u', 'u.id = rs.original_creator_id', 'left')
            ->where(['rs.claimed_content_id' => $id, 'rs.content_type' => 'VIDEO', 'rs.status' => 'ACTIVE'])
            ->get()->getRow();

        // [SECTION C] Heavy Monetization: Hybrid Ad Performance (Impressions, Views, Clicks)
        $ad_stats = $this->db->table('ad_impressions')->select("COUNT(id) as total_imps, SUM(cost) as total_rev")->where(['content_id' => $id, 'content_type' => 'video'])->get()->getRow();
        $ad_views_count = $this->db->tableExists('ad_views') ? $this->db->table('ad_views')->where(['content_id' => $id, 'content_type' => 'video'])->countAllResults() : 0;
        $ad_clicks = $this->db->tableExists('ad_clicks') ? $this->db->table('ad_clicks')->where(['content_id' => $id, 'content_type' => 'video'])->countAllResults() : 0;
        
        // 🔥 Real Earnings from creator_earnings table
        $creator_earnings = $this->db->table('creator_earnings')
            ->select('SUM(amount) as total_earnings')
            ->where(['content_id' => $id, 'content_type' => 'video'])
            ->get()->getRow();

        // [SECTION D] Deep Analytics: Watch behavior
        $retention = $this->db->table('video_watch_sessions')->select("AVG(watch_duration) as avg_watch, AVG(completion_rate) as avg_completion, SUM(watch_duration) as total_watch")->where(['video_id' => $id, 'video_type' => 'video'])->get()->getRow();

        // Audience Sentiment Feedback
        $feedbackArr = $this->db->table('user_content_feedback')->select('feedback_type, COUNT(id) as count')->where(['content_id' => $id, 'content_type' => 'video'])->groupBy('feedback_type')->get()->getResultArray();
        $feedback = array_column($feedbackArr, 'count', 'feedback_type');

        // FFmpeg Processing Logs
        $process_log = $this->db->table('video_processing_queue')->where(['video_id' => $id, 'video_type' => 'video'])->orderBy('id', 'DESC')->get()->getRow();

        // Preserving Engagement Rate and Viral Score Calculation
        $dislikes = $this->db->tableExists('video_dislikes') ? $this->db->table('video_dislikes')->where('video_id', $id)->countAllResults() : 0;
        $views = max(1, $video->views_count);
        $totalInteractions = $video->likes_count + $video->comments_count + $video->shares_count + $dislikes;
        $engagementRate = ($totalInteractions / $views) * 100;
        $rawViralScore = ($video->shares_count * 25) + ($video->comments_count * 15) + ($video->likes_count * 5) + ($views * 0.1);
        $finalScore = ($video->viral_score > 0) ? $video->viral_score : $rawViralScore;

        $stats = [
            'dislikes'        => $dislikes,
            'watch_time_hrs'  => round(($retention->total_watch ?? 0) / 3600, 2),
            'engagement_rate' => round($engagementRate, 2),
            'viral_score'     => round($finalScore, 0),    
            'viral_percent'   => min(100, round(($finalScore / 5000) * 100, 1)),
            'avg_completion'  => round($retention->avg_completion ?? 0, 1),
            'avg_watch_dur'   => round($retention->avg_watch ?? 0, 0)
        ];

        return view('admin/videos/view', [
            'video'            => $video, 
            'video_strike'     => $video_strike, 
            'comments'         => $this->db->table('comments c')->select('c.*, u.username, u.avatar')->join('users u', 'u.id = c.user_id', 'left')->where(['c.commentable_id' => $id, 'c.commentable_type' => 'video'])->orderBy('c.id', 'DESC')->limit(5)->get()->getResult(),
            'reports'          => $reports,
            'ad_stats'         => $ad_stats,
            'ad_views_count'   => $ad_views_count, 
            'ad_clicks'        => $ad_clicks,
            'creator_earnings' => $creator_earnings, 
            'rev_share'        => $rev_share, 
            'feedback'         => $feedback,
            'process_log'      => $process_log,
            'stats'            => $stats,
            'title'            => 'Video Intelligence HUD'
        ]);
    }

    /**
     * 3. ✏️ EDIT VIDEO (100% Preserved)
     */
    public function edit($id)
    {
        if (!has_permission('videos.edit')) return redirect()->back()->with('error', 'No permission');
        
        $video = $this->db->table('videos')->where('id', $id)->get()->getRow();
        if (!$video) return redirect()->to('admin/videos')->with('error', 'Video not found');
        
        $tagsData = $this->db->table('taggables t')->select('h.tag')->join('hashtags h', 'h.id = t.hashtag_id')->where(['t.taggable_id' => $id, 't.taggable_type' => 'video'])->get()->getResult();
        $video->tags = implode(', ', array_column($tagsData, 'tag'));
        
        return view('admin/videos/edit', ['title' => 'Refine Video Mapping', 'video' => $video]);
    }

    /**
     * 4. 🔄 UPDATE VIDEO (100% Preserved)
     */
    public function update($id)
    {
        if (!has_permission('videos.edit')) return redirect()->back();
        
        $data = [
            'title'                => esc($this->request->getPost('title')),
            'description'          => esc($this->request->getPost('description')),
            'category'             => $this->request->getPost('category'),
            'visibility'           => $this->request->getPost('visibility'),
            'status'               => $this->request->getPost('status'),
            'monetization_enabled' => $this->request->getPost('monetization_enabled') ? 1 : 0,
            'updated_at'           => date('Y-m-d H:i:s')
        ];

        if ($this->db->table('videos')->where('id', $id)->update($data)) {
            log_action('UPDATE_VIDEO', $id, 'video', "Metadata updated: " . $data['title']);
            return redirect()->to(base_url('admin/videos/view/'.$id))->with('success', 'Video updated successfully!');
        }
        return redirect()->back()->with('error', 'Failed.');
    }

    /**
     * 5. 🗑️ DELETE & FULL CLEANUP (Restored Original Logic)
     */
    public function delete($id)
    {
        if (!has_permission('videos.delete')) return redirect()->back();

        $video = $this->db->table('videos')->where('id', $id)->get()->getRow();
        if (!$video) return redirect()->to(base_url('admin/videos'))->with('error', 'Content already removed.');

        $videoUrl = $video->video_url;
        $thumbUrl = $video->thumbnail_url;

        try {
            $this->db->query("SET FOREIGN_KEY_CHECKS = 0");

            if ($this->db->tableExists('likes')) {
                $this->db->table('likes')->where('likeable_id', $id)->where('likeable_type', 'video')->delete();
            }

            if ($this->db->tableExists('taggables')) {
                $this->db->table('taggables')->where(['taggable_id' => $id, 'taggable_type' => 'video'])->delete();
            }
            if ($this->db->tableExists('comments')) {
                $this->db->table('comments')->where(['commentable_id' => $id, 'commentable_type' => 'video'])->delete();
            }

            $dependentTables = ['video_dislikes', 'video_views', 'video_watch_sessions', 'video_bookmarks', 'video_reports'];
            foreach ($dependentTables as $table) {
                if ($this->db->tableExists($table)) {
                    $this->db->table($table)->where('video_id', $id)->delete();
                }
            }

            if ($this->db->tableExists('notifications')) {
                $this->db->table('notifications')->where('notifiable_id', $id)->where('notifiable_type', 'video')->delete();
            }

            if ($this->db->tableExists('channel_strikes')) {
                $this->db->table('channel_strikes')->where('content_id', $id)->where('content_type', 'VIDEO')->delete();
                $this->db->table('channel_strikes')->where('original_content_id', $id)->update(['original_content_id' => null]);
            }
            if ($this->db->tableExists('copyright_blacklist')) {
                $this->db->table('copyright_blacklist')->where('original_video_id', $id)->delete();
            }

            $this->db->table('videos')->where('id', $id)->delete();
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");

            if (!empty($videoUrl) && function_exists('delete_media_master')) delete_media_master($videoUrl);
            if (!empty($thumbUrl) && function_exists('delete_media_master')) delete_media_master($thumbUrl);

            log_action('DELETE_VIDEO', $id, 'video', "Full wipe: " . $video->title);
            return redirect()->to(base_url('admin/videos'))->with('success', 'Video completely removed!');

        } catch (Throwable $e) {
            $this->db->query("SET FOREIGN_KEY_CHECKS = 1");
            die("BACKEND CRASH ERROR: " . $e->getMessage() . " on Line: " . $e->getLine());
        }
    }

    /**
     * 6. 🛠️ AJAX Handlers (100% Preserved)
     */
    public function update_status()
    {
        $id = $this->request->getPost('id');
        $status = $this->request->getPost('status');
        $this->db->table('videos')->where('id', $id)->update(['visibility' => $status]);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function issue_strike()
    {
        $v_id = $this->request->getPost('video_id');
        $c_id = $this->request->getPost('channel_id');
        $pts = $this->request->getPost('severity_points') ?? 10;
        
        $this->db->transStart();
        $this->db->table('channel_strikes')->insert([
            'channel_id' => $c_id, 'content_id' => $v_id, 'content_type' => 'VIDEO', 
            'reason' => $this->request->getPost('reason'), 'severity_points' => $pts, 'status' => 'ACTIVE'
        ]);
        $this->db->query("UPDATE channels SET trust_score = trust_score - $pts, strikes_count = strikes_count + 1 WHERE id = $c_id");
        $this->db->transComplete();
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function blacklist($id) 
    {
        $video = $this->db->table('videos')->where('id', $id)->get()->getRow();
        $this->db->table('copyright_blacklist')->insert([
            'banned_hash' => $video->video_hash, 
            'original_video_id' => $video->id, 
            'reason' => $this->request->getPost('reason'), 
            'banned_at' => date('Y-m-d H:i:s')
        ]);
        return redirect()->back();
    }
}
