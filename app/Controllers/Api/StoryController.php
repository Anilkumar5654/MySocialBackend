<?php namespace App\Controllers\Api; 

use App\Controllers\BaseController; 
use CodeIgniter\API\ResponseTrait; 

class StoryController extends BaseController { 
    use ResponseTrait; 
    
    protected $db; 
    
    public function __construct() { 
        date_default_timezone_set('Asia/Kolkata'); 
        helper(['media', 'url', 'text', 'filesystem', 'date']); 
        $this->db = \Config\Database::connect(); 
    } 
    
    public function getFeed() { 
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0; 
        $now = date('Y-m-d H:i:s');

        // 🔥 FIXED LOOPHOLE: Processing stories are only visible to the owner ($currentUserId).
        // For all other users, only 'published' stories are fetched.
        $sql = "SELECT s.*, u.username, u.name, u.avatar, u.is_verified, u.is_private, 
                m.title as music_title, m.artist as music_artist, m.audio_url as music_audio_url,
                (CASE WHEN sv.id IS NOT NULL THEN 1 ELSE 0 END) as is_viewed, 
                (CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END) as is_liked 
                FROM stories s 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN music m ON s.music_id = m.id
                LEFT JOIN follows f ON u.id = f.following_id AND f.follower_id = ? 
                LEFT JOIN story_views sv ON s.id = sv.story_id AND sv.user_id = ? 
                LEFT JOIN story_reactions sr ON s.id = sr.story_id AND sr.user_id = ? 
                LEFT JOIN blocks AS ubc ON ubc.blocker_id = ? AND ubc.blocked_entity_id = u.id AND ubc.blocked_type = 'user' 
                LEFT JOIN blocks AS cub ON cub.blocker_id = u.id AND cub.blocked_entity_id = ? AND cub.blocked_type = 'user' 
                WHERE s.expires_at > '{$now}' 
                AND (s.status = 'published' OR (s.status = 'processing' AND s.user_id = ?)) 
                AND u.is_banned = 0 
                AND (f.follower_id IS NOT NULL OR u.id = ?) 
                AND ubc.id IS NULL AND cub.id IS NULL 
                AND (u.is_private = 0 OR (f.follower_id IS NOT NULL) OR u.id = ?) 
                ORDER BY s.created_at DESC"; 
        
        $query = $this->db->query($sql, [ 
            $currentUserId, 
            $currentUserId, 
            $currentUserId, 
            $currentUserId, 
            $currentUserId, 
            $currentUserId, // user_id check for processing status
            $currentUserId, 
            $currentUserId 
        ]); 
        $results = $query->getResultArray(); 
        
        $stories = []; 
        foreach ($results as $row) { 
            $stories[] = [ 
                'id' => (string)$row['id'], 
                'user_id' => (string)$row['user_id'], 
                'media_url' => get_media_url($row['media_url']), 
                'media_type' => $row['media_type'], 
                'status' => $row['status'],
                'duration' => (int)$row['duration'], 
                'caption' => $row['caption'], 
                'music_id' => $row['music_id'] ? (string)$row['music_id'] : null,
                'music' => $row['music_id'] ? [
                    'id' => (string)$row['music_id'],
                    'title' => $row['music_title'],
                    'artist' => $row['music_artist'],
                    'audio_url' => $row['music_audio_url'] ? get_media_url($row['music_audio_url']) : null
                ] : null,
                'original_sound_muted' => (bool)($row['original_sound_muted'] ?? false),
                'is_separate_audio' => (bool)($row['is_separate_audio'] ?? false),
                'allow_download' => (bool)($row['allow_download'] ?? true),
                'created_at' => $row['created_at'], 
                'expires_at' => $row['expires_at'], 
                'views_count' => (int)$row['views_count'], 
                'likes_count' => (int)($row['likes_count'] ?? 0), 
                'is_viewed' => (bool)$row['is_viewed'], 
                'is_liked' => (bool)$row['is_liked'], 
                'user' => [ 
                    'id' => (string)$row['user_id'], 
                    'username' => $row['username'], 
                    'name' => $row['name'], 
                    'avatar' => get_media_url($row['avatar']), 
                    'is_verified' => (bool)$row['is_verified'] 
                ] 
            ]; 
        } 
        return $this->respond(['success' => true, 'stories' => $stories]); 
    } 
    
    /** * ✅ 2. UPLOAD STORY 
     */ 
    public function upload() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        $file = $this->request->getFile('file'); 

        // 🔥 HIGH-PRIORITY LOG: Check if request reached here
        $logFile = WRITEPATH . 'logs/story_debug.log';
        $logMsg = date('Y-m-d H:i:s') . " | Request In: User=$currentUserId | File=" . ($file ? $file->getName() : 'NULL') . PHP_EOL;
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        if (!$currentUserId) return $this->failUnauthorized(); 
        if (!$file || !$file->isValid()) { 
            file_put_contents($logFile, date('Y-m-d H:i:s') . " | Error: Invalid File Data" . PHP_EOL, FILE_APPEND);
            return $this->fail('Media file required', 400); 
        } 
        
        $mime = $file->getMimeType(); 
        $ext = strtolower($file->getClientExtension());
        $is_video = (strpos($mime, 'video') !== false); 

        // Log Format Details
        file_put_contents($logFile, date('Y-m-d H:i:s') . " | Details: MIME=$mime | EXT=$ext | IS_VIDEO=" . ($is_video ? 'YES' : 'NO') . PHP_EOL, FILE_APPEND);

        $allowedImageExts = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedVideoExts = ['mp4', 'mov', 'webm', '3gp', 'm4v']; // 🔥 Relaxed Extensions
        $allowedImageMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedVideoMimes = ['video/mp4', 'video/quicktime', 'video/webm', 'video/3gpp', 'video/x-m4v']; // 🔥 Relaxed MIMEs

        if ($is_video) {
            if (!in_array($mime, $allowedVideoMimes) || !in_array($ext, $allowedVideoExts)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " | Error: Format Rejected ($mime / $ext)" . PHP_EOL, FILE_APPEND);
                return $this->fail("Invalid video format ($ext).", 400);
            }
        } else {
            if (!in_array($mime, $allowedImageMimes) || !in_array($ext, $allowedImageExts)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " | Error: Format Rejected ($mime / $ext)" . PHP_EOL, FILE_APPEND);
                return $this->fail('Invalid image format.', 400);
            }
        }
        
        $mediaTypeFromApp = $this->request->getPost('media_type') ?? ($is_video ? 'video' : 'image');
        $originalMuted = $this->request->getPost('original_sound_muted') ?? '0';
        $isSeparateAudio = $this->request->getPost('is_separate_audio') ?? '0';
        $allowDownload = $this->request->getPost('allow_download') ?? '1';
        $musicId = $this->request->getPost('music_id') ?: null;

        // 🔥 FFmpeg Check for Storage Decision (Like Reels Controller)
        $ffmpegSetting = $this->db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow(); 
        $isFfmpegEnabled = ($ffmpegSetting && $ffmpegSetting->setting_value === 'true');
        
        $needsProcessing = ($isFfmpegEnabled && ($is_video || !empty($musicId)));
        $dbPath = null;

        if ($needsProcessing) {
            // 🔥 TEMP STORAGE LOGIC (Same as Reels)
            $tempDir = ROOTPATH . 'public/uploads/temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $newName = time() . '_' . $file->getRandomName();
            if ($file->move($tempDir, $newName)) {
                $dbPath = 'temp/' . $newName;
            }
        } else {
            // DIRECT STORAGE LOGIC
            $configType = $is_video ? 'story_video' : 'story'; 
            $dbPath = upload_media_master($file, $configType); 
        }
        
        if ($dbPath) { 
            $this->db->transStart(); 
            try {
                $now = date('Y-m-d H:i:s');
                $expires = date('Y-m-d H:i:s', strtotime($now . ' +24 hours'));

                $data = [ 
                    'user_id' => $currentUserId, 
                    'media_url' => $dbPath, 
                    'media_type' => $mediaTypeFromApp, 
                    'caption' => $this->request->getPost('caption') ?? '', 
                    'music_id' => $musicId, 
                    'original_sound_muted' => $originalMuted, 
                    'is_separate_audio' => $isSeparateAudio,   
                    'allow_download' => $allowDownload,       
                    'duration' => (int)($this->request->getPost('duration') ?? ($is_video ? 15 : 5)), 
                    'expires_at' => $expires, 
                    'created_at' => $now, 
                    'status' => $needsProcessing ? 'processing' : 'published' 
                ]; 
                
                $this->db->table('stories')->insert($data); 
                $newStoryId = $this->db->insertID(); 
                
                if ($needsProcessing) { 
                    $queueData = [ 
                        'video_id' => $newStoryId, 
                        'video_type' => 'story', 
                        'input_path' => $dbPath, 
                        'music_id' => $musicId,
                        'status' => 'pending', 
                        'created_at' => $now 
                    ];

                    $dbFields = $this->db->getFieldNames('video_processing_queue');
                    if (in_array('media_type', $dbFields)) $queueData['media_type'] = $mediaTypeFromApp;
                    if (in_array('original_sound_muted', $dbFields)) $queueData['original_sound_muted'] = $originalMuted;

                    $this->db->table('video_processing_queue')->insert($queueData); 
                }

                $this->db->transComplete(); 
                if ($this->db->transStatus() === FALSE) throw new \Exception("Transaction failed");

                file_put_contents($logFile, date('Y-m-d H:i:s') . " | Success: Story ID $newStoryId Saved" . PHP_EOL, FILE_APPEND);

                return $this->respondCreated(['success' => true, 'message' => 'Story uploaded', 'status' => $needsProcessing ? 'processing' : 'published']);

            } catch (\Exception $e) {
                $this->db->transRollback();
                file_put_contents($logFile, date('Y-m-d H:i:s') . " | Exception: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
                return $this->fail('Internal Error: ' . $e->getMessage(), 500);
            }
        } 
        file_put_contents($logFile, date('Y-m-d H:i:s') . " | Error: upload_media_master returned null" . PHP_EOL, FILE_APPEND);
        return $this->fail('File upload failed on server', 500); 
    } 

    public function deleteStory() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        $storyId = $this->request->getVar('story_id'); 
        if (!$storyId) return $this->fail('Story ID required', 400); 
        $story = $this->db->table('stories')->where(['id' => $storyId, 'user_id' => $currentUserId])->get()->getRow(); 
        if (!$story) return $this->failNotFound('Story not found'); 
        if ($story->media_url) delete_media_master($story->media_url); 
        $this->db->transStart();
        $this->db->table('story_views')->where('story_id', $storyId)->delete();
        $this->db->table('story_reactions')->where('story_id', $storyId)->delete();
        $this->db->table('stories')->where('id', $storyId)->delete(); 
        $this->db->transComplete();
        return $this->respond(['success' => true, 'message' => 'Story deleted']); 
    } 
    
    public function markViewed() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        if (!$currentUserId) return $this->failUnauthorized();
        $storyId = $this->request->getVar('story_id'); 
        if (!$storyId) return $this->fail('Story ID required', 400); 
        $storyExists = $this->db->table('stories')->where('id', $storyId)->countAllResults();
        if ($storyExists === 0) return $this->failNotFound('Story not found');
        $builder = $this->db->table('story_views'); 
        $exists = $builder->where(['user_id' => $currentUserId, 'story_id' => $storyId])->countAllResults(); 
        if ($exists == 0) { 
            $builder->insert(['user_id' => $currentUserId, 'story_id' => $storyId, 'viewed_at' => date('Y-m-d H:i:s')]); 
            $this->db->table('stories')->where('id', $storyId)->increment('views_count'); 
        } 
        return $this->respond(['success' => true]); 
    } 
    
    public function toggleReaction() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        if (!$currentUserId) return $this->failUnauthorized();
        $storyId = $this->request->getVar('story_id'); 
        if (!$storyId) return $this->fail('Story ID required', 400); 
        $storyExists = $this->db->table('stories')->where('id', $storyId)->countAllResults();
        if ($storyExists === 0) return $this->failNotFound('Story not found');
        $builder = $this->db->table('story_reactions'); 
        $existing = $builder->where(['user_id' => $currentUserId, 'story_id' => $storyId])->get()->getRow(); 
        if ($existing) { 
            $builder->where('id', $existing->id)->delete(); 
            $isLiked = false; 
        } else { 
            $builder->insert([ 
                'user_id' => $currentUserId, 
                'story_id' => $storyId, 
                'reaction_type' => $this->request->getVar('type') ?? 'heart', 
                'created_at' => date('Y-m-d H:i:s') 
            ]); 
            $isLiked = true; 
        } 
        $newCount = $this->db->table('stories')->select('likes_count')->where('id', $storyId)->get()->getRow()->likes_count ?? 0; 
        return $this->respond([ 'success' => true, 'is_liked' => $isLiked, 'likes_count' => (int)$newCount ]); 
    } 
    
    public function getViewers() { 
        $storyId = $this->request->getVar('story_id'); 
        if (!$storyId) return $this->fail('Story ID required', 400); 
        $viewers = $this->db->table('story_views sv') 
            ->select('u.id, u.username, u.name, u.avatar, u.is_verified, sv.viewed_at, sr.reaction_type') 
            ->join('users u', 'sv.user_id = u.id') 
            ->join('story_reactions sr', 'sr.story_id = sv.story_id AND sr.user_id = u.id', 'left') 
            ->where('sv.story_id', $storyId) 
            ->orderBy('sv.viewed_at', 'DESC') 
            ->get()->getResultArray(); 
        foreach ($viewers as &$v) { 
            $v['id'] = (string)$v['id']; 
            $v['avatar'] = get_media_url($v['avatar']); 
            $v['is_verified'] = (bool)$v['is_verified']; 
        } 
        return $this->respond(['success' => true, 'viewers' => $viewers]); 
    } 
}
