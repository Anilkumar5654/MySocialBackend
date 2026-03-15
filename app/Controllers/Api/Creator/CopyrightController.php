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
    // 🚀 1. AUTO-MATCHING ENGINE (SCANNER)
    // ==========================================
    public function scan()
    {
        $newVideos = $this->db->table('videos')
                        ->where('original_content_id', NULL)
                        ->where('frame_hashes !=', NULL)
                        ->get()->getResultArray();

        $scanCount = 0;
        foreach ($newVideos as $target) {
            $targetHashes = explode(',', $target['frame_hashes']);
            
            $originals = $this->db->table('videos')
                            ->where('id !=', $target['id'])
                            ->where('frame_hashes !=', NULL)
                            ->get()->getResultArray();

            foreach ($originals as $original) {
                $originalHashes = explode(',', $original['frame_hashes']);
                $common = array_intersect($targetHashes, $originalHashes);
                
                if (count($common) >= 7) { 
                    $this->db->table('videos')->where('id', $target['id'])->update([
                        'original_content_id' => $original['id']
                    ]);
                    $scanCount++;
                    break; 
                }
            }
        }
        return $this->respond(['status' => 'success', 'matches_found' => $scanCount]);
    }

    // ==========================================
    // 📱 2. MAIN LIST: ORIGINAL VIDEOS
    // ==========================================
    public function getOriginalVideos()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();

        $builder = $this->db->table('videos v');
        $builder->select('v.id, v.title, v.thumbnail_url as thumbnail, v.created_at as upload_date, v.views_count as views, v.duration');
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
    // 🔍 3. MATCH DETAILS (SMART REVENUE LOGIC)
    // ==========================================
    public function getMatchedClips($id = null)
    {
        if (!$id) return $this->fail('Video ID is required');

        // 🔥 Fetch dynamic revenue share from ad_settings table
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
        $builder->join('channel_strikes s', "s.content_id = v.id AND s.status != 'REJECTED'", 'left');
        
        $builder->where('v.original_content_id', $id);
        $builder->groupBy('v.id'); 
        $builder->orderBy('v.created_at', 'DESC');
        
        $matches = $builder->get()->getResultArray();

        foreach ($matches as &$m) {
            $m['thumb'] = get_media_url($m['thumb']);
            $m['upload_date'] = date('d M, Y', strtotime($m['upload_date']));
            
            // Frame Hash Comparison
            if (!empty($origHashes) && !empty($m['frame_hashes'])) {
                $matchHashes = explode(',', $m['frame_hashes']);
                $common = array_intersect($origHashes, $matchHashes);
                $m['match_percentage'] = round((count($common) / 10) * 100); 
            } else {
                $m['match_percentage'] = 0; 
            }

            // Status Mode Logic
            $mode = 'NONE';
            if ($m['s_status'] === 'ACTIVE' || $m['s_status'] === 'PENDING') {
                if ($m['s_type'] === 'PENDING_REVIEW') $mode = 'PENDING';
                elseif ($m['s_type'] === 'STRIKE') $mode = 'REMOVED';
                elseif ($m['s_type'] === 'CLAIM') $mode = 'CLAIMED';
            } elseif ($m['s_status'] === 'REJECTED') {
                $mode = 'REJECTED';
            } elseif ($m['phys_stat'] === 'STRIKED') {
                $mode = 'REMOVED';
            }

            $m['current_mode'] = $mode;
            
            // 🔥 Dynamic Key: Bhej rahe hain kitna share creator ko milega
            $m['rev_share_percent'] = $revShare;

            unset($m['frame_hashes'], $m['phys_stat'], $m['s_type'], $m['s_status']); 
        }

        return $this->respond(['status' => 'success', 'original_video' => $original, 'matches' => $matches]);
    }

    // ==========================================
    // ⚖️ 4. TAKE ACTION
    // ==========================================
    public function takeAction()
    {
        $json = $this->request->getJSON();
        $userId = $this->request->getHeaderLine('User-ID');

        if (!isset($json->matchedId)) return $this->fail('Invalid Request');

        $reporterChannel = $this->db->table('channels')->where('user_id', $userId)->get()->getRow();
        $offenderVideo = $this->db->table('videos')->where('id', $json->matchedId)->get()->getRow();

        $data = [
            'channel_id'          => $offenderVideo->channel_id,
            'reporter_channel_id' => $reporterChannel->id,
            'report_source'       => 'USER',
            'content_type'        => 'VIDEO',
            'content_id'          => $json->matchedId,
            'original_content_id' => $json->originalId,
            'reason'              => 'Copyright Match Found via Creator Studio',
            'type'                => ($json->actionType === 'STRIKE') ? 'PENDING_REVIEW' : 'CLAIM',
            'status'              => 'ACTIVE',
            'created_at'          => date('Y-m-d H:i:s')
        ];

        if ($this->db->table('channel_strikes')->insert($data)) {
            return $this->respond(['status' => 'success', 'message' => 'Action Submitted']);
        }
    }
}
