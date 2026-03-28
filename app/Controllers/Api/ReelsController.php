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

    /**
     * 🔥 FORMAT MUSIC - Helper function to format music object
     */
    private function formatMusicData($row)
    {
        if (!empty($row['music_id'])) {
            return [
                "id" => (string)$row['music_id'],
                "title" => $row['music_title'] ?? '',
                "artist" => $row['music_artist'] ?? '',
                "audio_url" => get_media_url($row['music_url'] ?? '', 'audio'),
                "cover_url" => get_media_url($row['music_cover'] ?? '', 'image')
            ];
        }
        return null;
    }

    // ✅ 1. GET SINGLE REEL DETAILS
    public function getDetails()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $id = $this->request->getVar('id');

        if (!$id) {
            return $this->fail(['error' => 'Reel ID is required']);
        }

        // 🔥 MODIFIED: Added music table join and extra flags
        $sql = "SELECT r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, 
                m.title as music_title, m.artist as music_artist, m.audio_url as music_url, m.cover_url as music_cover,
                (CASE WHEN l.id IS NOT NULL THEN 1 ELSE 0 END) as is_liked, 
                (CASE WHEN sv.id IS NOT NULL THEN 1 ELSE 0 END) as is_saved, 
                (CASE WHEN f.id IS NOT NULL THEN 1 ELSE 0 END) as is_following 
                FROM reels r 
                JOIN users u ON r.user_id = u.id 
                LEFT JOIN music m ON m.id = r.music_id
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

        // 🔥 MODIFIED: Music formatting
        $reel['music'] = $this->formatMusicData($reel);
        unset($reel['music_title'], $reel['music_artist'], $reel['music_url'], $reel['music_cover']);

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
        $builder->select('r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, m.title as music_title, m.artist as music_artist, m.audio_url as music_url, m.cover_url as music_cover');
        $builder->join('users u', 'r.user_id = u.id');
        $builder->join('music m', 'm.id = r.music_id', 'left');
        $builder->where('r.user_id', $targetUserId);
        $builder->where('r.status', 'published');
        $builder->where('r.visibility', 'public');

        if ($currentUserId > 0) {
            $escapedUserId = $this->db->escape($currentUserId);
            $builder->select("(CASE WHEN (SELECT 1 FROM likes WHERE likeable_id = r.id AND likeable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_liked");
            $builder->select("(CASE WHEN (SELECT 1 FROM saves WHERE saveable_id = r.id AND saveable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_saved");
            $builder->select("(SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = r.user_id LIMIT 1) as is_following");
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
            
            $row['music'] = $this->formatMusicData($row);
            unset($row['music_title'], $row['music_artist'], $row['music_url'], $row['music_cover']);
            
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
        $builder->select('r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, m.title as music_title, m.artist as music_artist, m.audio_url as music_url, m.cover_url as music_cover');
        $builder->join('users u', 'r.user_id = u.id');
        $builder->join('music m', 'm.id = r.music_id', 'left');
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
            $builder->select("(SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = r.user_id LIMIT 1) as is_following");
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
            
            $row['music'] = $this->formatMusicData($row);
            unset($row['music_title'], $row['music_artist'], $row['music_url'], $row['music_cover']);
            
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

    /**
     * ✅ 4. TRACK WATCH TIME
     */
    public function trackWatch()
    {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $this->request->getPost();
        $id = $input['reel_id'] ?? $input['id'] ?? null;
        $watchDuration = (int)($input['watch_duration'] ?? 0);
        $userId = $this->request->getHeaderLine('User-ID') ?: null;
        $ipAddress = $this->request->getIPAddress();
        $timestamp = date('Y-m-d H:i:s');

        $trafficSource = $input['traffic_source'] ?? 'shorts_feed';

        if (!$id || $watchDuration < 1) return $this->respond(['success' => true]);

        $reel = $this->db->table('reels')->select('id, duration, user_id')
            ->groupStart()->where('id', $id)->orWhere('unique_id', $id)->groupEnd()
            ->get()->getRowArray();

        if (!$reel) return $this->failNotFound();
        
        $actualId = $reel['id'];
        $totalDuration = (int)$reel['duration'];

        try {
            $this->db->transStart();

            $existingView = $this->db->table('views')
                ->where(['viewable_id' => $actualId, 'viewable_type' => 'reel'])
                ->groupStart()
                    ->where('user_id', $userId)
                    ->orWhere('ip_address', $ipAddress)
                ->groupEnd()
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 minutes')))
                ->orderBy('id', 'DESC')
                ->get()->getRow();

            if ($existingView) {
                $newDuration = $existingView->watch_duration + $watchDuration;
                $newCompletion = ($totalDuration > 0) ? min(100, ($newDuration / $totalDuration) * 100) : 0;

                $this->db->table('views')->where('id', $existingView->id)->update([
                    'watch_duration'  => $newDuration,
                    'completion_rate' => $newCompletion,
                    'updated_at'      => $timestamp
                ]);
            } else {
                $completionRate = ($totalDuration > 0) ? min(100, ($watchDuration / $totalDuration) * 100) : 0;
                $this->db->table('views')->insert([
                    'user_id'         => $userId,
                    'creator_id'      => $reel['user_id'],
                    'viewable_id'     => $actualId,
                    'viewable_type'   => 'reel',
                    'watch_duration'  => $watchDuration,
                    'completion_rate' => $completionRate,
                    'ip_address'      => $ipAddress,
                    'traffic_source'  => $trafficSource,
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ]);
            }

            $realCount = $this->db->table('views')
                ->where(['viewable_id' => $actualId, 'viewable_type' => 'reel'])
                ->countAllResults();

            $this->db->table('reels')->where('id', $actualId)->update(['views_count' => $realCount]);

            $this->db->transComplete();

            return $this->respond(['success' => true, 'synced_views' => $realCount]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
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
        $builder->select('r.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, c.trust_score as channel_trust_score, m.title as music_title, m.artist as music_artist, m.audio_url as music_url, m.cover_url as music_cover');
        $builder->join('users u', 'r.user_id = u.id');
        $builder->join('channels c', 'r.channel_id = c.id', 'left');
        $builder->join('music m', 'm.id = r.music_id', 'left');
        $builder->where('r.status', 'published');
        $builder->where('r.visibility', 'public');

        if ($currentUserId > 0) {
            $escapedUserId = $this->db->escape($currentUserId);
            $builder->select("(CASE WHEN (SELECT 1 FROM likes WHERE likeable_id = r.id AND likeable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_liked");
            $builder->select("(CASE WHEN (SELECT 1 FROM saves WHERE saveable_id = r.id AND saveable_type = 'reel' AND user_id = {$escapedUserId}) THEN 1 ELSE 0 END) as is_saved");
            $builder->select("(SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = r.user_id LIMIT 1) as is_following");
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
            
            $row['music'] = $this->formatMusicData($row);
            unset($row['music_title'], $row['music_artist'], $row['music_url'], $row['music_cover']);
            
            $row = $this->normalizeKeys($row);
        }

        $adEngine = new EngineController();
        $injected = $adEngine->inject_into_feed($reels, 'reel');
        foreach ($injected as &$item) {
            $item = $this->normalizeKeys($item);
        }

        return $this->respond(['success' => true, 'reels' => $injected, 'hasMore' => count($reels) == $limit, 'seed' => (int)$seed]);
    }

    // ✅ 6. UPLOAD REEL (UPGRADED FOR IMAGE/VIDEO & SEPARATE AUDIO)
    public function upload()
    {
        set_time_limit(0);
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized();

        $channel = $this->db->table('channels')->where('user_id', $currentUserId)->get()->getRow();
        if (!$channel) return $this->failForbidden('Channel not found.');

        // 🔥 Support both Image and Video
        $mediaFile = $this->request->getFile('video') ?? $this->request->getFile('file');
        if (!$mediaFile || !$mediaFile->isValid()) return $this->fail('Media file missing or invalid.');

        // 🔥 Fetching New Level Flags from App
        $musicId = $this->request->getPost('music_id') ?: null;
        $mediaType = $this->request->getPost('media_type') ?? 'video'; // image or video
        $originalMuted = $this->request->getPost('original_sound_muted') ?? '0';
        $isSeparateAudio = $this->request->getPost('is_separate_audio') ?? '0';
        $allowDownload = $this->request->getPost('allow_download') ?? '1';

        $ffmpegSetting = $this->db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow();
        $isFfmpegEnabled = ($ffmpegSetting && $ffmpegSetting->setting_value === 'true');

        $mediaDbPath = null;
        // If image or forced mute, we must process via FFmpeg queue
        $needsProcessing = ($isFfmpegEnabled && ($mediaType === 'image' || $originalMuted === '1' || $isFfmpegEnabled));

        if ($needsProcessing) {
            $tempDir = ROOTPATH . 'public/uploads/temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $newName = time() . '_' . $mediaFile->getRandomName();
            if ($mediaFile->move($tempDir, $newName)) {
                $mediaDbPath = 'temp/' . $newName;
            }
        } else {
            $mediaDbPath = upload_media_master($mediaFile, 'reel');
        }

        if (!$mediaDbPath) return $this->failServerError('Upload Failed');

        $this->db->transStart();

        $data = [
            'user_id' => $currentUserId,
            'channel_id' => $channel->id,
            'music_id' => $musicId,
            'video_url' => $mediaDbPath,
            'caption' => $this->request->getPost('caption') ?? '',
            'duration' => (int)($this->request->getPost('duration') ?? 0),
            'visibility' => $this->request->getPost('visibility') ?? 'public',
            'media_type' => $mediaType, // 🔥 Saved for backend logic
            'original_sound_muted' => $originalMuted, // 🔥 Mute flag
            'is_separate_audio' => $isSeparateAudio, // 🔥 Copyright flag
            'allow_download' => $allowDownload, // 🔥 Security flag
            'status' => $needsProcessing ? 'processing' : 'published',
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (!empty($musicId)) {
            $this->db->query("UPDATE music SET usage_count = usage_count + 1 WHERE id = ?", [$musicId]);
        }

        $this->db->table('reels')->insert($data);
        $newReelId = $this->db->insertID();

        $actualReelsCount = $this->db->table('reels')->where('user_id', $currentUserId)->countAllResults();
        $this->db->table('users')->where('id', $currentUserId)->update(['reels_count' => $actualReelsCount]);

        if ($needsProcessing) {
            $this->db->table('video_processing_queue')->insert([
                'video_id' => $newReelId,
                'channel_id' => $channel->id,
                'video_type' => 'reel',
                'input_path' => $mediaDbPath,
                'media_type' => $mediaType, // Image needs video conversion
                'original_sound_muted' => $originalMuted,
                'music_id' => $musicId,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        if (!empty($data['caption'])) {
            $this->hashtagHelper->syncHashtags($newReelId, 'reel', $data['caption']);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->failServerError('Upload failed.');
        }

        return $this->respondCreated(['success' => true, 'reel_id' => (string)$newReelId]);
    }

    // ✅ 7. DELETE REEL
    public function delete($id = null)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$id) $id = $this->request->getVar('id');

        $reel = $this->db->table('reels')->where('user_id', $userId)->groupStart()->where('id', $id)->orWhere('unique_id', $id)->groupEnd()->get()->getRow();
        
        if ($reel) {
            $this->db->transStart();

            if (!empty($reel->video_url)) {
                delete_media_master($reel->video_url);
            }

            if (!empty($reel->music_id)) {
                $this->db->query("UPDATE music SET usage_count = GREATEST(usage_count - 1, 0) WHERE id = ?", [$reel->music_id]);
            }

            $this->db->table('reels')->where('id', $reel->id)->delete();

            $actualReelsCount = $this->db->table('reels')->where('user_id', $userId)->countAllResults();
            $this->db->table('users')->where('id', $userId)->update(['reels_count' => $actualReelsCount]);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->failServerError('Delete failed.');
            }

            return $this->respondDeleted(['success' => true]);
        }
        return $this->failNotFound();
    }
}
