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
     * 1. 📋 VIDEO LISTING (Untouched as requested)
     */
    public function index()
    {
        if (!has_permission('videos.view')) return redirect()->to('admin/dashboard')->with('error', 'Access Denied');

        $builder = $this->db->table('videos v');
        $builder->select('v.*, u.username, u.avatar as user_avatar, u.is_verified as user_verified, c.name as channel_name, c.handle as channel_handle');
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
        if ($category = $this->request->getGet('category')) $builder->where('v.category', $category);

        // Stats calculation using your actual schema tables
        $stats = [
            'total_videos'    => $this->db->table('videos')->countAllResults(),
            'total_views'     => $this->db->table('channels')->selectSum('total_views')->get()->getRow()->total_views ?? 0,
            'flagged_count'   => $this->db->table('reports')->where('status', 'pending')->countAllResults(),
            'monetized_count' => $this->db->table('channels')->where('is_monetization_enabled', 1)->countAllResults(),
        ];

        $data = [
            'title'  => "Video Management | All Videos", 
            'stats'  => $stats,
            'videos' => $builder->orderBy('v.id', 'DESC')->get()->getResult()
        ];

        return view('admin/videos/index', $data);
    }

    /**
     * 2. 👁️ VIEW VIDEO DETAILS (Upgraded to Real Ad Tables)
     */
    public function view($id)
    {
        if (!has_permission('videos.view')) return redirect()->to('admin/dashboard');

        // [SECTION 1] Core Video, Creator, and Channel Info
        $video = $this->db->table('videos v')
            ->select('v.*, u.username, u.name as full_name, u.avatar as user_avatar, u.is_verified as user_verified, u.followers_count,
                      c.name as channel_name, c.id as channel_id, c.unique_id as channel_unique_id, c.videos_count as channel_videos_count,
                      c.strikes_count, c.warnings_count, IFNULL(c.trust_score, 100) as trust_score, 
                      ov.video_url as original_video_url, ov.thumbnail_url as original_thumbnail, ou.name as original_owner_name, 
                      ov.created_at as original_created_at')
            ->join('users u', 'u.id = v.user_id', 'left')
            ->join('channels c', 'c.id = v.channel_id', 'left')
            ->join('videos ov', 'ov.id = v.original_content_id', 'left')
            ->join('users ou', 'ou.id = ov.user_id', 'left')
            ->where('v.id', $id)
            ->get()->getRow();

        if (!$video) return redirect()->to('admin/videos')->with('error', 'Video not found.');

        // Tags Logic
        $tagsData = $this->db->table('taggables t')->select('h.tag')->join('hashtags h', 'h.id = t.hashtag_id')->where(['t.taggable_id' => $id, 't.taggable_type' => 'video'])->get()->getResult();
        $video->tags = implode(', ', array_column($tagsData, 'tag'));

        // [SECTION 2] Reports & Strikes
        $reports = $this->db->table('reports r')->select('r.*, u.username as reporter_name')->join('users u', 'u.id = r.reporter_id', 'left')->where(['reportable_id' => $id, 'reportable_type' => 'video'])->orderBy('r.id', 'DESC')->get()->getResult();

        $video_strike = $this->db->table('channel_strikes')
            ->where(['content_id' => $id, 'content_type' => 'VIDEO', 'status' => 'ACTIVE'])
            ->orderBy('id', 'DESC')->get()->getRow();

        // [SECTION 3] Monetization & Payouts (REAL LOGIC)
        $rev_share = $this->db->table('revenue_shares rs')
            ->select('rs.*, u.username as claimant_username')
            ->join('users u', 'u.id = rs.original_creator_id', 'left')
            ->where(['rs.claimed_content_id' => $id, 'rs.content_type' => 'VIDEO', 'rs.status' => 'ACTIVE'])
            ->get()->getRow();

        // Real Earnings
        $creator_earnings = $this->db->table('creator_earnings')
            ->select('SUM(amount) as total_earnings')
            ->where(['content_id' => $id, 'content_type' => 'video'])
            ->get()->getRow();
            
        $total_earnings = $creator_earnings->total_earnings ?? 0;

        // Real Ad Impressions from your new ad_impressions table
        $total_imps = $this->db->tableExists('ad_impressions') 
            ? $this->db->table('ad_impressions')->where(['content_id' => $id, 'content_type' => 'video'])->countAllResults()
            : 0;

        // Dynamic RPM Calculation
        $avg_rpm = ($total_imps > 0) ? ($total_earnings / $total_imps) * 1000 : 0;

        $ad_stats = (object)[
            'total_imps' => $total_imps,
            'avg_rpm'    => number_format($avg_rpm, 2)
        ];

        // [SECTION 4] Deep Analytics & Watch Time (From your `views` table)
        $retention = $this->db->table('views')
            ->select("AVG(watch_duration) as avg_watch, AVG(completion_rate) as avg_completion, SUM(watch_duration) as total_watch")
            ->where(['viewable_id' => $id, 'viewable_type' => 'video'])
            ->get()->getRow();

        // Trend calculations (Last 7 Days vs Total)
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
        
        $recent_views = $this->db->table('views')->where(['viewable_id' => $id, 'viewable_type' => 'video', 'created_at >=' => $seven_days_ago])->countAllResults();
        $recent_likes = $this->db->table('likes')->where(['likeable_id' => $id, 'likeable_type' => 'video', 'created_at >=' => $seven_days_ago])->countAllResults();
        $recent_comments = $this->db->table('comments')->where(['commentable_id' => $id, 'commentable_type' => 'video', 'created_at >=' => $seven_days_ago])->countAllResults();

        // Graph Data (Views by Day)
        $graph_data = $this->db->query("
            SELECT DATE(created_at) as date, COUNT(id) as count 
            FROM views 
            WHERE viewable_id = ? AND viewable_type = 'video' AND created_at >= ?
            GROUP BY DATE(created_at) 
            ORDER BY date ASC
        ", [$id, $seven_days_ago])->getResult();

        // [SECTION 5] Audience Insights (Geo, Age, Device)
        $geo_stats = $this->db->query("
            SELECT u.country, COUNT(v.id) as count 
            FROM views v 
            JOIN users u ON u.id = v.user_id 
            WHERE v.viewable_id = ? AND v.viewable_type = 'video' 
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
            WHERE v.viewable_id = ? AND v.viewable_type = 'video' AND u.dob IS NOT NULL
            GROUP BY age_group
        ", [$id])->getResult();

        $device_stats = $this->db->query("
            SELECT a.device_type, COUNT(v.id) as count 
            FROM views v 
            JOIN auth_tokens a ON a.user_id = v.user_id 
            WHERE v.viewable_id = ? AND v.viewable_type = 'video' AND a.device_type IS NOT NULL
            GROUP BY a.device_type
        ", [$id])->getResult();

        $process_log = $this->db->tableExists('video_processing_queue') ? $this->db->table('video_processing_queue')->where(['video_id' => $id, 'video_type' => 'video'])->orderBy('id', 'DESC')->get()->getRow() : null;

        $stats = [
            'watch_time_hrs'  => round(($retention->total_watch ?? 0) / 3600, 1),
            'avg_watch_dur'   => gmdate("i:s", (int)($retention->avg_watch ?? 0)),
            'avg_completion'  => round($retention->avg_completion ?? 0, 1),
            'recent_views'    => $recent_views,
            'recent_likes'    => $recent_likes,
            'recent_comments' => $recent_comments
        ];

        return view('admin/videos/view', [
            'video'            => $video, 
            'video_strike'     => $video_strike, 
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
            'title'            => 'Video Deep Dive'
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

            $dependentTables = ['video_dislikes', 'video_views', 'views', 'video_watch_sessions', 'video_bookmarks', 'reports', 'ad_impressions', 'ad_clicks'];
            foreach ($dependentTables as $table) {
                if ($this->db->tableExists($table)) {
                    $column = ($table == 'reports') ? 'reportable_id' : (($table == 'views') ? 'viewable_id' : (($table == 'ad_impressions' || $table == 'ad_clicks') ? 'content_id' : 'video_id'));
                    $this->db->table($table)->where($column, $id)->delete();
                }
            }

            if ($this->db->tableExists('channel_strikes')) {
                $this->db->table('channel_strikes')->where('content_id', $id)->where('content_type', 'VIDEO')->delete();
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
     * 6. 🛠️ BULK ACTIONS
     */
    public function bulk_action()
    {
        $ids = $this->request->getPost('ids');
        $action = $this->request->getPost('action');

        if (empty($ids) || !has_permission('videos.edit')) return $this->response->setJSON(['status' => 'error']);

        if ($action == 'delete' && has_permission('videos.delete')) {
            foreach ($ids as $id) $this->delete($id);
        } elseif ($action == 'monetize_on') {
            $this->db->table('videos')->whereIn('id', $ids)->update(['monetization_enabled' => 1]);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * 7. 🛠️ AJAX Handlers (Synced with Schema Strikes & Blacklist)
     */
    public function issue_strike()
    {
        $v_id = $this->request->getPost('video_id');
        $c_id = $this->request->getPost('channel_id');
        $pts = $this->request->getPost('severity_points') ?? 10;
        
        $this->db->transStart();
        // Inserting into your 'channel_strikes' table
        $this->db->table('channel_strikes')->insert([
            'channel_id' => $c_id, 
            'content_id' => $v_id, 
            'content_type' => 'VIDEO', 
            'reason' => $this->request->getPost('reason'), 
            'severity_points' => $pts, 
            'status' => 'ACTIVE',
            'report_source' => 'MANUAL_ADMIN'
        ]);
        // Deducting points from your 'channels' table (Trigger will cap it at 0)
        $this->db->query("UPDATE channels SET trust_score = trust_score - $pts, strikes_count = strikes_count + 1 WHERE id = $c_id");
        $this->db->transComplete();
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function blacklist($id) 
    {
        $video = $this->db->table('videos')->where('id', $id)->get()->getRow();
        // Inserting into your 'copyright_blacklist' table
        $this->db->table('copyright_blacklist')->insert([
            'banned_hash' => $video->video_hash ?? md5($video->id), 
            'original_video_id' => $video->id, 
            'reason' => $this->request->getPost('reason'), 
            'banned_at' => date('Y-m-d H:i:s')
        ]);
        return redirect()->back()->with('success', 'Video blacklisted.');
    }
}
