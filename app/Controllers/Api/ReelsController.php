<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\Api\Ads\EngineController;
use App\Helpers\InteractionHelper;
use App\Helpers\HashtagHelper;

class ReelsController extends BaseController
{
    use ResponseTrait;

    protected $db;
    protected $interaction;
    protected $hashtagHelper;

    public function __construct()
    {
        helper(['media', 'url', 'text', 'filesystem', 'form', 'trust_score_helper', 'session']);
        $this->db = \Config\Database::connect();
        $this->interaction = new InteractionHelper();
        $this->hashtagHelper = new HashtagHelper();
    }

    /**
     * 🔥 NORMALIZER
     */
    private function normalizeKeys(array $item): array
    {
        $boolKeys = ['is_liked', 'is_saved', 'is_following', 'is_verified'];
        foreach ($boolKeys as $key) {
            if (array_key_exists($key, $item)) {
                $item[$key] = (bool)$item[$key];
            }
        }

        foreach (array_keys($item) as $key) {
            if (preg_match('/[A-Z]/', $key)) {
                unset($item[$key]);
            }
        }

        foreach ($item as $key => $value) {
            if (is_array($value)) {
                $item[$key] = $this->normalizeKeys($value);
            }
        }
        return $item;
    }

    // ✅ 1. GET SINGLE REEL DETAILS
    public function getDetails()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $id = $this->request->getVar('id');

        if (!$id) {
            return $this->fail(['error' => 'Reel ID is required']);
        }

        $sql = "SELECT r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, 
                (CASE WHEN l.id IS NOT NULL THEN 1 ELSE 0 END) as is_liked, 
                (CASE WHEN sv.id IS NOT NULL THEN 1 ELSE 0 END) as is_saved, 
                (CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) as is_following 
                FROM reels r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN likes l ON r.id = l.likeable_id AND l.likeable_type = 'reel' AND l.user_id = ? 
                LEFT JOIN saves sv ON r.id = sv.saveable_id AND sv.saveable_type = 'reel' AND sv.user_id = ? 
                LEFT JOIN follows f ON f.follower_id = ? AND f.following_id = r.user_id 
                WHERE (r.id = ? OR r.unique_id = ?) AND r.status = 'published' LIMIT 1";

        $query = $this->db->query($sql, [$currentUserId, $currentUserId, $currentUserId, $id, $id]);
        $reel = $query->getRowArray();

        if (!$reel) {
            return $this->failNotFound('Reel not found or private.');
        }

        $reel['id'] = (string)$reel['id'];
        $reel['type'] = 'reel';
        $reel['video_url'] = get_media_url($reel['video_url']);
        $reel['thumbnail_url'] = get_media_url($reel['thumbnail_url']);
        $reel['user_avatar'] = get_media_url($reel['user_avatar']);
        $reel['user'] = [
            "id" => (string)$reel['user_id'],
            "username" => $reel['handle'],
            "name" => $reel['display_name'],
            "avatar" => $reel['user_avatar'],
            "is_verified" => $reel['user_verified'],
            "is_following" => (bool)($reel['is_following'] ?? false)
        ];

        return $this->respond(['success' => true, 'reel' => $this->normalizeKeys($reel)]);
    }

    // ✅ 2. GET BY USER
    public function getByUser()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $targetUserId = $this->request->getVar('user_id');
        $page = (int)($this->request->getVar('page') ?? 1);
        $limit = (int)($this->request->getVar('limit') ?? 12);
        $offset = ($page - 1) * $limit;

        if (!$targetUserId) {
            return $this->fail(['error' => 'User ID is required']);
        }

        $totalCount = $this->db->table('reels')
                             ->where(['user_id' => $targetUserId, 'status' => 'published', 'visibility' => 'public'])
                             ->countAllResults();

        $builder = $this->db->table('reels r');
        $builder->select('r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified');
        $builder->join('users u', 'r.user_id = u.id');
        $builder->where('r.user_id', $targetUserId);
        $builder->where('r.status', 'published');
        $builder->where('r.visibility', 'public');

        if ($currentUserId > 0) {
            $escapedUserId = $this->db->escape($currentUserId);
            $builder->select("(CASE WHEN (SELECT 1 FROM likes WHERE likeable_id = r.id AND likeable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_liked");
            $builder->select("(CASE WHEN (SELECT 1 FROM saves WHERE saveable_id = r.id AND saveable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_saved");
            $builder->select("(CASE WHEN (SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = r.user_id) THEN 1 ELSE 0 END) as is_following");
        }

        $builder->orderBy('r.created_at', 'DESC');
        $reels = $builder->get($limit, $offset)->getResultArray();

        foreach ($reels as &$row) {
            $row['id'] = (string)$row['id'];
            $row['type'] = 'reel';
            $row['video_url'] = get_media_url($row['video_url']);
            $row['thumbnail_url'] = get_media_url($row['thumbnail_url']);
            $row['user_avatar'] = get_media_url($row['user_avatar']);
            $row['user'] = [
                "id" => (string)$row['user_id'],
                "username" => $row['handle'],
                "name" => $row['display_name'],
                "avatar" => $row['user_avatar'],
                "is_verified" => $row['user_verified'],
                "is_following" => (bool)($row['is_following'] ?? false)
            ];
            $row = $this->normalizeKeys($row);
        }

        $hasMore = ($offset + count($reels)) < $totalCount;

        return $this->respond([
            'success' => true, 
            'reels' => $reels, 
            'hasMore' => $hasMore, 
            'currentPage' => $page
        ]);
    }

    // ✅ 3. GET EXPLORE FEED
    public function getExploreFeed()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $targetUserId = $this->request->getVar('user_id');
        $excludeReelId = $this->request->getVar('reel_id');
        $page = (int)($this->request->getVar('page') ?? 1);
        $limit = (int)($this->request->getVar('limit') ?? 12);
        $offset = ($page - 1) * $limit;

        if (!$targetUserId) return $this->fail(['error' => 'User ID is required']);

        $countBuilder = $this->db->table('reels');
        $countBuilder->where(['user_id' => $targetUserId, 'status' => 'published', 'visibility' => 'public']);
        if ($excludeReelId) {
            $countBuilder->groupStart()->where('id !=', $excludeReelId)->where('unique_id !=', $excludeReelId)->groupEnd();
        }
        $totalCount = $countBuilder->countAllResults();

        $builder = $this->db->table('reels r');
        $builder->select('r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified');
        $builder->join('users u', 'r.user_id = u.id');
        $builder->where('r.user_id', $targetUserId);

        if ($excludeReelId) {
            $builder->groupStart()->where('r.id !=', $excludeReelId)->where('r.unique_id !=', $excludeReelId)->groupEnd();
        }

        $builder->where('r.status', 'published');
        $builder->where('r.visibility', 'public');

        if ($currentUserId > 0) {
            $escapedUserId = $this->db->escape($currentUserId);
            $builder->select("(CASE WHEN (SELECT 1 FROM likes WHERE likeable_id = r.id AND likeable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_liked");
            $builder->select("(CASE WHEN (SELECT 1 FROM saves WHERE saveable_id = r.id AND saveable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_saved");
            $builder->select("(CASE WHEN (SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = r.user_id) THEN 1 ELSE 0 END) as is_following");
        }

        $builder->orderBy('r.created_at', 'DESC');
        $reels = $builder->get($limit, $offset)->getResultArray();

        foreach ($reels as &$row) {
            $row['id'] = (string)$row['id'];
            $row['type'] = 'reel';
            $row['video_url'] = get_media_url($row['video_url']);
            $row['thumbnail_url'] = get_media_url($row['thumbnail_url']);
            $row['user_avatar'] = get_media_url($row['user_avatar']);
            $row['user'] = [
                "id" => (string)$row['user_id'],
                "username" => $row['handle'],
                "name" => $row['display_name'],
                "avatar" => $row['user_avatar'],
                "is_verified" => $row['user_verified'],
                "is_following" => (bool)($row['is_following'] ?? false)
            ];
            $row = $this->normalizeKeys($row);
        }

        $hasMore = ($offset + count($reels)) < $totalCount;

        return $this->respond([
            'success' => true, 
            'reels' => $reels, 
            'hasMore' => $hasMore, 
            'currentPage' => $page
        ]);
    }

    // ✅ 4. TRACK WATCH TIME (REAL RETENTION FIXED)
    public function trackWatch()
    {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $this->request->getPost();
        $id = $input['reel_id'] ?? $input['id'] ?? null;
        $watchDuration = (int)($input['watch_duration'] ?? 0);
        $deviceId = $input['device_id'] ?? null;
        $userId = $this->request->getHeaderLine('User-ID') ?: null;

        if (!$id) return $this->fail(['error' => 'Reel ID missing']);
        if ($watchDuration < 1) return $this->respond(['success' => true]);

        // 1. Duration fetch karo completion rate ke liye
        $reel = $this->db->table('reels')->select('id, user_id, channel_id, duration')
            ->groupStart()->where('id', $id)->orWhere('unique_id', $id)->groupEnd()
            ->get()->getRowArray();

        if (!$reel) return $this->failNotFound('Reel not found.');
        
        $actualId = $reel['id'];
        $totalDuration = (int)$reel['duration'];

        try {
            // 🔥 REAL RETENTION CALCULATION
            $completionRate = 0;
            if ($totalDuration > 0) {
                $completionRate = ($watchDuration / $totalDuration) * 100;
                if ($completionRate > 100) $completionRate = 100; 
            }

            $sessionUserId = !empty($userId) ? $userId : null;
            $ipAddress = $this->request->getIPAddress();
            $today = date('Y-m-d');
            
            // 2. Check existing session for today
            $sessionBuilder = $this->db->table('video_watch_sessions')
                ->where('video_id', $actualId)
                ->where('video_type', 'reel')
                ->where('created_at >=', $today . ' 00:00:00');

            if ($sessionUserId) {
                $sessionBuilder->where('user_id', $sessionUserId);
            } else {
                $sessionBuilder->where('device_id', $deviceId)->where('ip_address', $ipAddress);
            }

            $existingSession = $sessionBuilder->get()->getRow();
            
            if ($existingSession) {
                if ($watchDuration > $existingSession->watch_duration) {
                    $this->db->table('video_watch_sessions')->where('id', $existingSession->id)->update([
                        'watch_duration' => $watchDuration,
                        'completion_rate' => $completionRate, // ✅ Update rate
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            } else {
                // Naya session with real completion rate
                $this->db->table('video_watch_sessions')->insert([
                    'user_id' => $sessionUserId,
                    'video_id' => $actualId,
                    'video_type' => 'reel',
                    'watch_duration' => $watchDuration,
                    'completion_rate' => $completionRate, // ✅ Save rate
                    'device_id' => $deviceId,
                    'ip_address' => $ipAddress,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Naya session hai toh view count bhi badhao
                $this->db->table('reels')->where('id', $actualId)->increment('views_count');
            }

            return $this->respond(['success' => true]);
        } catch (\Exception $e) {
            return $this->failServerError('Track Error: ' . $e->getMessage());
        }
    }

    // ✅ 5. GET FEED
    public function getFeed()
    {
        $session = \Config\Services::session();
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $page = (int)($this->request->getVar('page') ?? 1);
        $limit = (int)($this->request->getVar('limit') ?? 10);
        $offset = ($page - 1) * $limit;

        if ($page == 1) {
            $seed = (int)(microtime(true) * 1000);
            $session->set('reels_feed_seed', $seed);
        } else {
            $seed = $session->get('reels_feed_seed') ?: (int)(microtime(true) * 1000);
        }

        $builder = $this->db->table('reels r');
        $builder->select('r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, c.trust_score as channel_trust_score');
        $builder->join('users u', 'r.user_id = u.id');
        $builder->join('channels c', 'r.channel_id = c.id', 'left');
        $builder->where('r.status', 'published');
        $builder->where('r.visibility', 'public');

        if ($currentUserId > 0) {
            $escapedUserId = $this->db->escape($currentUserId);
            $builder->select("(CASE WHEN (SELECT 1 FROM likes WHERE likeable_id = r.id AND likeable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_liked");
            $builder->select("(CASE WHEN (SELECT 1 FROM saves WHERE saveable_id = r.id AND saveable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_saved");
            $builder->select("(CASE WHEN (SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = r.user_id) THEN 1 ELSE 0 END) as is_following");
        }

        $builder->orderBy("CASE WHEN r.created_at >= NOW() - INTERVAL 3 DAY THEN 1 ELSE 2 END", "ASC");
        $builder->orderBy("RAND($seed)");

        $reels = $builder->get($limit, $offset)->getResultArray();

        foreach ($reels as &$row) {
            $row['id'] = (string)$row['id'];
            $row['type'] = 'reel';
            $row['video_url'] = get_media_url($row['video_url']);
            $row['thumbnail_url'] = get_media_url($row['thumbnail_url']);
            $row['user_avatar'] = get_media_url($row['user_avatar']);
            $row['user'] = [
                "id" => (string)$row['user_id'],
                "username" => $row['handle'],
                "name" => $row['display_name'],
                "avatar" => $row['user_avatar'],
                "is_verified" => $row['user_verified'],
                "is_following" => (bool)($row['is_following'] ?? false)
            ];
            $row = $this->normalizeKeys($row);
        }

        $adEngine = new EngineController();
        $injected = $adEngine->inject_into_feed($reels, 'reel');
        foreach ($injected as &$item) {
            $item = $this->normalizeKeys($item);
        }

        return $this->respond(['success' => true, 'reels' => $injected, 'hasMore' => count($reels) == $limit, 'seed' => (int)$seed]);
    }

    // ✅ 6. UPLOAD REEL
    public function upload()
    {
        set_time_limit(0);
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized();

        $channel = $this->db->table('channels')->where('user_id', $currentUserId)->get()->getRow();
        if (!$channel) return $this->failForbidden('Channel not found.');

        $videoFile = $this->request->getFile('video') ?? $this->request->getFile('file');
        if (!$videoFile || !$videoFile->isValid()) return $this->fail('Video file missing or invalid.');

        $ffmpegSetting = $this->db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow();
        $isFfmpegEnabled = ($ffmpegSetting && $ffmpegSetting->setting_value === 'true');

        $videoDbPath = null;
        if ($isFfmpegEnabled) {
            $tempDir = FCPATH . 'uploads/temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $newName = time() . '_' . $videoFile->getRandomName();
            if ($videoFile->move($tempDir, $newName)) {
                $videoDbPath = 'temp/' . $newName;
            }
        } else {
            $videoDbPath = upload_media_master($videoFile, 'reel');
        }

        if (!$videoDbPath) return $this->failServerError('Upload Failed');

        $uniqueId = 'REL' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

        $data = [
            'user_id' => $currentUserId,
            'channel_id' => $channel->id,
            'unique_id' => $uniqueId,
            'video_url' => $videoDbPath,
            'caption' => $this->request->getPost('caption') ?? '',
            'duration' => (int)($this->request->getPost('duration') ?? 0),
            'visibility' => $this->request->getPost('visibility') ?? 'public',
            'status' => $isFfmpegEnabled ? 'processing' : 'published',
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->table('reels')->insert($data)) {
            $newReelId = $this->db->insertID();

            if ($isFfmpegEnabled) {
                $this->db->table('video_processing_queue')->insert([
                    'video_id' => $newReelId,
                    'channel_id' => $channel->id,
                    'video_type' => 'reel',
                    'input_path' => $videoDbPath,
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            if (!empty($data['caption'])) {
                $this->hashtagHelper->syncHashtags($newReelId, 'reel', $data['caption']);
            }
            return $this->respondCreated(['success' => true, 'reel_id' => (string)$newReelId, 'unique_id' => $uniqueId]);
        }
        return $this->failServerError('DB Insert Failed');
    }

    // ✅ 7. DELETE REEL
    public function delete($id = null)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$id) $id = $this->request->getVar('id');

        $reel = $this->db->table('reels')->where('user_id', $userId)->groupStart()->where('id', $id)->orWhere('unique_id', $id)->groupEnd()->get()->getRow();
        if ($reel) {
            if (!empty($reel->video_url)) {
                delete_media_master($reel->video_url);
            }
            $this->db->table('reels')->where('id', $reel->id)->delete();
            return $this->respondDeleted(['success' => true]);
        }
        return $this->failNotFound();
    }
}
