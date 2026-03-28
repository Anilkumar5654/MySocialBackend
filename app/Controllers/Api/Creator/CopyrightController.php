<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class CopyrightController extends BaseController
{
    use ResponseTrait;
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        helper(['media', 'date', 'number']);
    }

    /**
     * Helper to get dynamic ad settings
     */
    private function getAdSetting($key, $default = 0)
    {
        $row = $this->db->table('ad_settings')
                    ->where('setting_key', $key)
                    ->get()->getRow();
        return $row ? $row->setting_value : $default;
    }

    // ==========================================
    // 📱 1. MAIN LIST: ORIGINAL VIDEOS
    // ==========================================
    public function getOriginalVideos()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();

        $builder = $this->db->table('videos v');
        $builder->select('v.id, v.title, v.thumbnail_url as thumbnail, v.created_at as upload_date, v.views_count as views, v.duration');
        // Matches count logic stays here as it's UI dependent
        $builder->select("(SELECT COUNT(DISTINCT id) FROM videos WHERE original_content_id = v.id AND channel_id != v.channel_id) as matches");
        
        $builder->where('v.user_id', $userId);
        $builder->having('matches > 0');
        $builder->orderBy('v.created_at', 'DESC');

        $data = $builder->get()->getResultArray();
        foreach ($data as &$row) {
            $row['thumbnail'] = get_media_url($row['thumbnail']);
            $row['upload_date'] = date('d M, Y', strtotime($row['upload_date']));
        }
        return $this->respond(['status' => 'success', 'data' => $data]);
    }

    // ==========================================
    // 🔍 2. MATCH DETAILS (SMART REVENUE LOGIC)
    // ==========================================
    public function getMatchedClips($id = null)
    {
        if (!$id) return $this->fail('Video ID is required');

        $revShare = $this->getAdSetting('revenue_share_original_creator', 70);

        $original = $this->db->table('videos')
                        ->select('id, title, thumbnail_url as thumb, created_at as upload_date, views_count as views, duration, frame_hashes')
                        ->where('id', $id)
                        ->get()->getRowArray();

        $origHashes = [];
        if ($original) {
            $original['thumb'] = get_media_url($original['thumb']);
            $original['upload_date'] = date('d M, Y', strtotime($original['upload_date']));
            $origHashes = !empty($original['frame_hashes']) ? explode(',', $original['frame_hashes']) : [];
        }

        $builder = $this->db->table('videos v');
        $builder->select('v.id, v.title, v.thumbnail_url as thumb, v.created_at as upload_date, v.duration, c.name as uploader, v.frame_hashes');
        
        $builder->select('v.copyright_status as phys_stat'); 
        $builder->select('s.type as s_type, s.status as s_status'); 
        
        $builder->join('channels c', 'c.id = v.channel_id');
        // 🔥 FIX 1: Removed "!= REJECTED" and explicitly set content_type to properly show full status lifecycle
        $builder->join('channel_strikes s', "s.content_id = v.id AND s.content_type = 'VIDEO'", 'left');
        
        $builder->where('v.original_content_id', $id);
        $builder->groupBy('v.id'); 
        $builder->orderBy('v.created_at', 'DESC');
        
        $matches = $builder->get()->getResultArray();

        foreach ($matches as &$m) {
            $m['thumb'] = get_media_url($m['thumb']);
            $m['upload_date'] = date('d M, Y', strtotime($m['upload_date']));
            
            if (!empty($origHashes) && !empty($m['frame_hashes'])) {
                $matchHashes = explode(',', $m['frame_hashes']);
                $common = array_intersect($origHashes, $matchHashes);
                $m['match_percentage'] = round((count($common) / 10) * 100); 
            } else {
                $m['match_percentage'] = 0; 
            }

            $mode = 'NONE';
            
            // 🔥 FIX 2: Full Lifecycle Handling (Restored & Rejected)
            if ($m['s_status']) {
                if ($m['s_status'] === 'APPEAL_APPROVED') {
                    $mode = 'RESTORED';
                } elseif ($m['s_status'] === 'REJECTED') {
                    $mode = 'REJECTED';
                } elseif ($m['s_status'] === 'EXPIRED') {
                    $mode = 'RESTORED';
                } elseif ($m['s_status'] === 'ACTIVE' || $m['s_status'] === 'PENDING') {
                    if ($m['s_type'] === 'PENDING_REVIEW') $mode = 'PENDING';
                    elseif ($m['s_type'] === 'STRIKE') $mode = 'REMOVED';
                    elseif ($m['s_type'] === 'CLAIM') $mode = 'CLAIMED';
                }
            } elseif ($m['phys_stat'] === 'STRIKED') {
                $mode = 'REMOVED';
            }

            $m['current_mode'] = $mode;
            $m['rev_share_percent'] = $revShare;

            unset($m['frame_hashes'], $m['phys_stat'], $m['s_type'], $m['s_status']); 
        }

        return $this->respond(['status' => 'success', 'original_video' => $original, 'matches' => $matches]);
    }

    // ==========================================
    // ⚖️ 3. TAKE ACTION (Bulletproof Version)
    // ==========================================
    public function takeAction()
    {
        $json = $this->request->getJSON();
        $userId = $this->request->getHeaderLine('User-ID');

        if (!isset($json->matchedId)) return $this->fail('Invalid Request');

        $contentType = strtoupper($json->contentType ?? 'VIDEO');
        $targetTable = ($contentType === 'REEL') ? 'reels' : 'videos';

        $reporterChannel = $this->db->table('channels')->where('user_id', $userId)->get()->getRow();
        $offenderContent = $this->db->table($targetTable)->where('id', $json->matchedId)->get()->getRow();

        if (!$offenderContent || !$reporterChannel) {
            return $this->fail('Content or Channel not found');
        }

        // 🔥 FIX 3: STRICT DUPLICATE CHECK (One action per content allowed)
        $existingAction = $this->db->table('channel_strikes')
            ->where('content_id', $json->matchedId)
            ->where('content_type', $contentType)
            ->countAllResults();

        if ($existingAction > 0) {
            return $this->fail('An action has already been taken or reviewed for this content. You cannot submit it again.');
        }

        // 🔥 FIX 4: BULLETPROOF ORIGINAL ID FALLBACK
        $originalId = $json->originalId ?? $offenderContent->original_content_id ?? null;

        if (!$originalId) {
            return $this->fail('Original Video ID is missing. Cannot process action.');
        }

        $data = [
            'channel_id'          => $offenderContent->channel_id,
            'reporter_channel_id' => $reporterChannel->id,
            'report_source'       => 'USER',
            'content_type'        => $contentType,
            'content_id'          => $json->matchedId,
            'original_content_id' => $originalId,
            'reason'              => 'Copyright Match Found via Creator Studio',
            'type'                => ($json->actionType === 'STRIKE') ? 'PENDING_REVIEW' : 'CLAIM',
            'status'              => 'ACTIVE'
            // 'created_at' removed so DB handles it naturally
        ];

        if ($this->db->table('channel_strikes')->insert($data)) {
            return $this->respond(['status' => 'success', 'message' => 'Action Submitted Successfully. It is now pending review.']);
        }

        return $this->fail('Failed to process request');
    }
}
