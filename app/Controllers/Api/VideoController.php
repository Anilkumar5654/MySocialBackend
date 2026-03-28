<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\VideoModel;
use App\Models\ChannelModel;
use App\Models\AdsModel;
use App\Controllers\Api\Ads\EngineController;
use App\Helpers\InteractionHelper;
use App\Helpers\HashtagHelper;

class VideoController extends BaseController
{
    use ResponseTrait;

    protected $videoModel;
    protected $channelModel;
    protected $adsModel;
    protected $db;
    protected $interaction;
    protected $hashtagHelper;

    public function __construct()
    {
        $this->videoModel = new VideoModel();
        $this->channelModel = new ChannelModel();
        $this->adsModel = new AdsModel();
        $this->db = \Config\Database::connect();
        $this->interaction = new InteractionHelper();
        $this->hashtagHelper = new HashtagHelper();
        helper(['media', 'url', 'text', 'filesystem']);
    }

    private function getRequestInput()
    {
        $rawInput = file_get_contents('php://input');
        $decoded = json_decode($rawInput, true) ?: [];
        return array_merge($this->request->getGet() ?? [], $this->request->getPost() ?? [], $decoded);
    }

    private function processMediaUrl($rawPath)
    {
        if (empty($rawPath)) return null;
        if (strpos($rawPath, 'default-placeholder') !== false) return null;
        if (filter_var($rawPath, FILTER_VALIDATE_URL)) return $rawPath;
        $fullUrl = get_media_url($rawPath);
        return (strpos($fullUrl, 'default-placeholder') !== false) ? null : $fullUrl;
    }

    private function getMaxUploadSizeMB()
    {
        $setting = $this->db->table('system_settings')->where('setting_key', 'max_upload_size_mb')->get()->getRow();
        return $setting ? (int)$setting->setting_value : 100;
    }

    /**
     * 🔥 NORMALIZER
     */
    private function normalizeKeys(array $item): array
    {
        $boolKeys = ['is_following', 'is_subscribed', 'is_liked', 'is_disliked', 'is_saved', 'user_verified', 'channel_verified'];
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
     * ✅ 4. GET VIDEOS
     */
    public function getVideos()
    {
        try {
            $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
            $escapedUserId = $this->db->escape($currentUserId);
            $page = (int)($this->request->getVar('page') ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $builder = $this->videoModel->builder();
            $builder->select('videos.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, channels.trust_score as channel_trust_score, u.followers_count');
            $builder->select("(CASE WHEN (SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = videos.user_id LIMIT 1) THEN 1 ELSE 0 END) as is_following");
            $builder->join('users u', 'u.id = videos.user_id', 'left');
            $builder->join('channels', 'channels.id = videos.channel_id', 'left');
            $builder->where(['videos.status' => 'published', 'videos.visibility' => 'public', 'videos.copyright_status' => 'NONE']);

            if ($currentUserId > 0) {
                $builder->whereNotIn('videos.user_id', function ($subquery) use ($currentUserId) {
                    return $subquery->select('blocked_entity_id')->from('blocks')->where('blocker_id', $currentUserId)->where('blocked_type', 'user');
                });
                $builder->whereNotIn('videos.user_id', function ($subquery) use ($currentUserId) {
                    return $subquery->select('blocker_id')->from('blocks')->where('blocked_entity_id', $currentUserId)->where('blocked_type', 'user');
                });
            }

            $builder->orderBy('videos.created_at', 'DESC');
            $builder->orderBy('videos.viral_score', 'DESC');
            $builder->orderBy('RAND(' . (date('H') + $page) . ')');

            $videos = $builder->get($limit, $offset)->getResultArray();

            foreach ($videos as &$v) {
                $v['id'] = (string)$v['id'];
                $v['type'] = 'video';
                $v['video_url'] = $this->processMediaUrl($v['video_url']);
                $v['thumbnail_url'] = $this->processMediaUrl($v['thumbnail_url']);
                $v['user_avatar'] = $this->processMediaUrl($v['user_avatar']);
                $v['channel_name'] = $v['display_name'];
                $v['channel_handle'] = $v['handle'];
                $v['channel_avatar'] = $v['user_avatar'];
                $v['channel_verified'] = $v['user_verified'];
                $v['followers_count'] = (int)($v['followers_count'] ?? 0);
                $v['subscribers_count'] = $v['followers_count'];
                $v['is_subscribed'] = (bool)($v['is_following'] ?? false);
                $v = $this->normalizeKeys($v);
            }

            $adEngine = new EngineController();
            $items = $adEngine->inject_into_feed($videos, 'video');

            foreach ($items as &$item) {
                $item = $this->normalizeKeys($item);
            }

            return $this->respond([
                'items' => $items,
                'hasMore' => count($videos) === $limit
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * ✅ 5. RECOMMENDED VIDEOS
     */
    public function getRecommended()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $input = $this->getRequestInput();
        $excludeId = $input['exclude_id'] ?? $input['video_id'] ?? $input['id'] ?? null;

        $builder = $this->videoModel->builder();
        $builder->select('videos.*, u.name as display_name, u.username as handle, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, u.followers_count');
        $builder->join('users u', 'u.id = videos.user_id', 'left');
        $builder->where(['videos.status' => 'published', 'videos.visibility' => 'public']);

        if ($excludeId) {
            $builder->where('videos.id !=', $excludeId)->where('videos.unique_id !=', $excludeId);
        }

        if ($currentUserId > 0) {
            $builder->whereNotIn('videos.user_id', function ($subquery) use ($currentUserId) {
                return $subquery->select('blocked_entity_id')->from('blocks')->where('blocker_id', $currentUserId)->where('blocked_type', 'user');
            });
        }

        $builder->orderBy('videos.viral_score', 'DESC');
        $videos = $builder->get(12)->getResultArray();

        foreach ($videos as &$v) {
            $v['id'] = (string)$v['id'];
            $v['type'] = 'video';
            $v['user_avatar'] = $this->processMediaUrl($v['user_avatar']);
            $v['channel_name'] = $v['display_name'];
            $v['channel_handle'] = $v['handle'];
            $v['channel_avatar'] = $v['user_avatar'];
            $v['channel_verified'] = $v['user_verified'];
            $v['video_url'] = $this->processMediaUrl($v['video_url']);
            $v['thumbnail_url'] = $this->processMediaUrl($v['thumbnail_url']);
            $v = $this->normalizeKeys($v);
        }

        $adEngine = new EngineController();
        $items = $adEngine->inject_into_feed($videos, 'video');

        foreach ($items as &$item) {
            $item = $this->normalizeKeys($item);
        }
        return $this->respond(['items' => $items]);
    }

    /**
     * ✅ 6. SEARCH
     */
    public function search()
    {
        try {
            $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
            $q = trim((string)($this->request->getGet('q') ?? ''));
            $type = $this->request->getGet('type') ?? 'all';

            if ($q === '') return $this->respond(['items' => []]);

            $videoResults = [];
            $channelResults = [];

            if ($type === 'all' || $type === 'video') {
                $vBuilder = $this->videoModel->builder();
                $vBuilder->select('videos.*, u.name as display_name, u.username as handle, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, u.followers_count, "video" as type');
                $vBuilder->join('users u', 'u.id = videos.user_id', 'left');
                $vBuilder->where(['videos.status' => 'published', 'videos.visibility' => 'public']);

                if ($currentUserId > 0) {
                    $vBuilder->whereNotIn('videos.user_id', function ($subquery) use ($currentUserId) {
                        return $subquery->select('blocked_entity_id')->from('blocks')->where('blocker_id', $currentUserId)->where('blocked_type', 'user');
                    });
                }

                $vBuilder->groupStart()->like('videos.title', $q)->orLike('videos.description', $q)->groupEnd();
                $vBuilder->orderBy('videos.viral_score', 'DESC');
                $videos = $vBuilder->limit($type === 'video' ? 30 : 15)->get()->getResultArray();

                foreach ($videos as &$v) {
                    $v['id'] = (string)$v['id'];
                    $v['user_avatar'] = $this->processMediaUrl($v['user_avatar']);
                    $v['channel_name'] = $v['display_name'];
                    $v['channel_avatar'] = $v['user_avatar'];
                    $v['thumbnail_url'] = $this->processMediaUrl($v['thumbnail_url']);
                    $v['video_url'] = $this->processMediaUrl($v['video_url']);
                    $v = $this->normalizeKeys($v);
                }

                $adEngine = new EngineController();
                $videoResults = ($type === 'all') ? $adEngine->inject_into_feed($videos, 'video') : $videos;

                if ($type === 'all') {
                    foreach ($videoResults as &$item) {
                        $item = $this->normalizeKeys($item);
                    }
                }
            }

            if ($type === 'all' || $type === 'channel') {
                $cBuilder = $this->db->table('users u');
                $cBuilder->select('u.id as user_id, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.is_verified, u.videos_count, u.followers_count, "channel" as type');
                $cBuilder->groupStart()->like('u.name', $q)->orLike('u.username', $q)->groupEnd();
                $cBuilder->where('u.is_deleted', 0);

                if ($currentUserId > 0) {
                    $cBuilder->whereNotIn('u.id', function ($subquery) use ($currentUserId) {
                        return $subquery->select('blocked_entity_id')->from('blocks')->where('blocker_id', $currentUserId)->where('blocked_type', 'user');
                    });
                }

                $channels = $cBuilder->limit($type === 'channel' ? 20 : 2)->get()->getResultArray();
                foreach ($channels as &$c) {
                    $c['id'] = (string)$c['user_id'];
                    $c['name'] = $c['display_name'];
                    $c['user_avatar'] = $this->processMediaUrl($c['user_avatar']);
                    $c['avatar'] = $c['user_avatar'];
                    $c['followers_count'] = (int)($c['followers_count'] ?? 0);
                    $c = $this->normalizeKeys($c);
                }
                $channelResults = $channels;
            }

            $finalResults = ($type === 'channel') ? $channelResults : (($type === 'video') ? $videoResults : array_merge($videoResults, $channelResults));
            return $this->respond(['items' => $finalResults]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * ✅ 7. TRACK WATCH TIME (PERMANENT SYNC & UPSERT LOGIC)
     */
    public function trackWatch()
    {
        $input = $this->getRequestInput();
        $id = $input['video_id'] ?? $input['id'] ?? null;
        $watchDuration = (int)($input['watch_duration'] ?? 0);
        $userId = $this->request->getHeaderLine('User-ID') ?: null;
        $ipAddress = $this->request->getIPAddress();
        $timestamp = date('Y-m-d H:i:s');
        
        // 🔥 FIXED: Traffic source pakdo aur 'direct' default rakho
        $trafficSource = $input['traffic_source'] ?? 'direct';

        if (!$id || $watchDuration < 1) return $this->respond(['success' => true]);

        $video = $this->videoModel->select('id, duration, user_id')->groupStart()->where('id', $id)->orWhere('unique_id', $id)->groupEnd()->first();
        if (!$video) return $this->failNotFound();
        
        $actualId = $video['id'];
        $totalDuration = (int)$video['duration'];

        try {
            $this->db->transStart();

            // 🔥 Logic: Find if a session exists for this user/ip in the last 30 minutes
            $existingView = $this->db->table('views')
                ->where(['viewable_id' => $actualId, 'viewable_type' => 'video'])
                ->groupStart()
                    ->where('user_id', $userId)
                    ->orWhere('ip_address', $ipAddress)
                ->groupEnd()
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime('-30 minutes')))
                ->orderBy('id', 'DESC')
                ->get()->getRow();

            if ($existingView) {
                // ✅ UPDATE EXISTING SESSION (No new view counted)
                $newDuration = $existingView->watch_duration + $watchDuration;
                $newCompletion = ($totalDuration > 0) ? min(100, ($newDuration / $totalDuration) * 100) : 0;

                $this->db->table('views')->where('id', $existingView->id)->update([
                    'watch_duration'  => $newDuration,
                    'completion_rate' => $newCompletion,
                    'updated_at'      => $timestamp
                ]);
            } else {
                // ✅ CREATE NEW SESSION (Counts as +1 View)
                $completionRate = ($totalDuration > 0) ? min(100, ($watchDuration / $totalDuration) * 100) : 0;
                $this->db->table('views')->insert([
                    'user_id'         => $userId,
                    'creator_id'      => $video['user_id'],
                    'viewable_id'     => $actualId,
                    'viewable_type'   => 'video',
                    'watch_duration'  => $watchDuration,
                    'completion_rate' => $completionRate,
                    'ip_address'      => $ipAddress,
                    'traffic_source'  => $trafficSource, 
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ]);
            }

            // 🔥 FORCE SYNC: Calculate total unique sessions for real view count
            $realCount = $this->db->table('views')
                ->where(['viewable_id' => $actualId, 'viewable_type' => 'video'])
                ->countAllResults();

            $this->videoModel->update($actualId, ['views_count' => $realCount]);

            $this->db->transComplete();

            return $this->respond([
                'success' => true, 
                'synced_views' => (int)$realCount
            ]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * ✅ 8. GET DETAILS
     */
    public function getDetails()
    {
        $input = $this->getRequestInput();
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $escapedUserId = $this->db->escape($currentUserId);
        $id = $input['id'] ?? $input['video_id'] ?? null;

        if (!$id) return $this->fail('Video ID is required.');

        $builder = $this->videoModel->builder();
        $builder->select('videos.*, u.name as display_name, u.username as handle, u.avatar as user_avatar, u.id as user_id, u.is_verified as user_verified, u.followers_count');
        $builder->select("(CASE WHEN (SELECT 1 FROM follows WHERE follower_id = {$escapedUserId} AND following_id = videos.user_id LIMIT 1) THEN 1 ELSE 0 END) as is_following");
        
        // 🔥 NAYA LOGIC: Fetch notification preference from follows table
        $builder->select("(SELECT notification_pref FROM follows WHERE follower_id = {$escapedUserId} AND following_id = videos.user_id LIMIT 1) as notification_pref");
        
        $builder->join('users u', 'u.id = videos.user_id', 'left');

        $builder->groupStart()
            ->where('videos.id', $id)
            ->orWhere('videos.unique_id', $id)
            ->groupEnd();

        $video = $builder->get()->getRowArray();
        if (!$video) return $this->failNotFound();

        $actualId = $video['id'];
        $video['is_liked'] = $this->db->table('likes')->where(['likeable_id' => $actualId, 'likeable_type' => 'video', 'user_id' => $currentUserId])->countAllResults() > 0;
        $video['is_disliked'] = $this->db->table('video_dislikes')->where(['video_id' => $actualId, 'user_id' => $currentUserId])->countAllResults() > 0;
        $video['is_saved'] = $this->db->table('saves')->where(['saveable_id' => $actualId, 'saveable_type' => 'video', 'user_id' => $currentUserId])->countAllResults() > 0;

        // 🔥 Added likes_count and is_liked subquery to recentComment securely
        $recentComment = $this->db->table('comments')
            ->select('
                comments.content as text, 
                users.avatar as user_avatar, 
                users.name as user_name,
                comments.likes_count,
                (SELECT COUNT(*) FROM likes WHERE likeable_type = "comment" AND likeable_id = comments.id AND user_id = ' . $escapedUserId . ') as is_liked
            ')
            ->join('users', 'users.id = comments.user_id')
            ->where('comments.commentable_id', $actualId)
            ->where('comments.commentable_type', 'video')
            ->orderBy('comments.created_at', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($recentComment) {
            $recentComment['user_avatar'] = $this->processMediaUrl($recentComment['user_avatar']);
            $recentComment['is_liked'] = (bool)$recentComment['is_liked'];
            $recentComment['likes_count'] = (int)$recentComment['likes_count'];
            $video['recent_comment'] = $recentComment;
        }

        $video['id'] = (string)$video['id'];
        $video['video_url'] = $this->processMediaUrl($video['video_url']);
        $video['thumbnail_url'] = $this->processMediaUrl($video['thumbnail_url']);
        $video['user_avatar'] = $this->processMediaUrl($video['user_avatar']);
        $video['channel_name'] = $video['display_name'];
        $video['channel_handle'] = $video['handle'];
        $video['channel_avatar'] = $video['user_avatar'];
        $video['channel_verified'] = $video['user_verified'];
        $video['followers_count'] = (int)($video['followers_count'] ?? 0);
        $video['subscribers_count'] = $video['followers_count'];
        $video['is_following'] = (bool)($video['is_following'] ?? false);
        $video['is_subscribed'] = $video['is_following'];
        
        // Ensure notification_pref is not null
        $video['notification_pref'] = $video['notification_pref'] ?: 'personalized';
        
        $video = $this->normalizeKeys($video);

        $adEngine = new EngineController();
        return $this->respond(['video' => $video, 'ad_break' => $adEngine->fetch_instream_raw_data()]);
    }

    /**
     * ✅ 9. UPLOAD VIDEO
     */
    public function upload()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();

        $videoFile = $this->request->getFile('video');
        $thumbFile = $this->request->getFile('thumbnail');
        if (!$videoFile || !$videoFile->isValid()) {
            return $this->fail('Invalid video file.');
        }

        $channel = $this->db->table('channels')->where('user_id', $userId)->get()->getRowArray();
        if (!$channel) return $this->failForbidden('Channel not found.');

        $ffmpegSetting = $this->db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow();
        $isFfmpegEnabled = ($ffmpegSetting && $ffmpegSetting->setting_value === 'true');

        $videoDbPath = null;
        if ($isFfmpegEnabled) {
            $tempDir = ROOTPATH . 'public/uploads/temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $newName = time() . '_' . $videoFile->getRandomName();
            if ($videoFile->move($tempDir, $newName)) {
                $videoDbPath = 'temp/' . $newName;
            }
        } else {
            $videoDbPath = upload_media_master($videoFile, 'video_file');
        }

        if (!$videoDbPath) return $this->failServerError('Upload logic failed.');

        $rawTags = $this->request->getVar('tags');
        $tagsArray = $rawTags ? array_map('trim', explode(',', $rawTags)) : [];
        $uniqueId = 'VID' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

        $isMonetized = ($this->request->getVar('monetization_enabled') === 'true' || $this->request->getVar('monetization_enabled') == 1) ? 1 : 0;

        $data = [
            'user_id' => $userId,
            'channel_id' => $channel['id'],
            'unique_id' => $uniqueId,
            'title' => $this->request->getVar('title'),
            'description' => $this->request->getVar('description'),
            'category' => $this->request->getVar('category') ?: 'General',
            'visibility' => $this->request->getVar('visibility') ?: 'public',
            'duration' => $this->request->getVar('duration') ?: 0,
            'video_url' => $videoDbPath,
            'thumbnail_url' => ($thumbFile && $thumbFile->isValid()) ? upload_media_master($thumbFile, 'video_thumbnail') : null,
            'monetization_enabled' => $isMonetized,
            'status' => $isFfmpegEnabled ? 'processing' : 'published',
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->videoModel->insert($data)) {
            $videoId = $this->videoModel->getInsertID();

            if ($isFfmpegEnabled) {
                $this->db->table('video_processing_queue')->insert([
                    'video_id'   => $videoId,
                    'channel_id' => $channel['id'],
                    'video_type' => 'video',
                    'input_path' => $videoDbPath,
                    'status'     => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            if (!empty($tagsArray)) {
                $this->hashtagHelper->sync('video', $videoId, $tagsArray);
            }
            return $this->respondCreated(['success' => true, 'video_id' => (string)$videoId, 'unique_id' => $uniqueId]);
        }
        return $this->failServerError();
    }

    /**
     * ✅ 10. UPDATE VIDEO
     */
    public function update($id)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        $video = $this->videoModel->where('id', $id)->orWhere('unique_id', $id)->first();
        if (!$video || $video['user_id'] != $userId) return $this->failForbidden();

        $data = [
            'title' => $this->request->getVar('title') ?: $video['title'],
            'description' => $this->request->getVar('description') ?: $video['description'],
            'visibility' => $this->request->getVar('visibility') ?: $video['visibility']
        ];

        if ($this->videoModel->update($video['id'], $data)) {
            return $this->respond(['success' => true]);
        }
        return $this->failServerError();
    }

    /**
     * ✅ 11. DELETE VIDEO (SYNCED WITH USER COUNTER)
     */
    public function delete($id)
    {
        $userId = $this->request->getHeaderLine('User-ID');
        $video = $this->videoModel->where('id', $id)->orWhere('unique_id', $id)->first();
        
        if (!$video || $video['user_id'] != $userId) {
            return $this->failForbidden();
        }

        try {
            $this->db->transStart();

            // 1. Files delete karo (Thumbnail & Video)
            if (!empty($video['video_url']) && file_exists(FCPATH . $video['video_url'])) {
                @unlink(FCPATH . $video['video_url']);
            }
            if (!empty($video['thumbnail_url']) && file_exists(FCPATH . $video['thumbnail_url'])) {
                @unlink(FCPATH . $video['thumbnail_url']);
            }

            // 2. Database se video delete karo
            $this->videoModel->delete($video['id']);

            // 🔥 3. Users table mein videos_count minus karo
            $this->db->table('users')
                ->where('id', $userId)
                ->set('videos_count', 'videos_count - 1', false)
                ->update();

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->failServerError('Failed to delete video and sync counter.');
            }

            return $this->respond(['success' => true]);
        } catch (\Exception $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
