<?php namespace App\Controllers\Api; 

use App\Controllers\BaseController; 
use CodeIgniter\API\ResponseTrait; 

class StoryController extends BaseController { 
    use ResponseTrait; 
    
    protected $db; 
    
    public function __construct() { 
        // Sabhi helpers load 
        helper(['media', 'url', 'text', 'filesystem', 'date']); 
        $this->db = \Config\Database::connect(); 
    } 
    
    /** * ✅ 1. GET FEED (Consistency & Guest Crash Fix) 
     */ 
    public function getFeed() { 
        // 🔥 FIX: Prevent SQL crash for guest users
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0; 
        
        $sql = "SELECT s.id, s.user_id, s.media_url, s.media_type, s.duration, s.caption, s.created_at, s.expires_at, s.views_count, s.likes_count, u.username, u.name, u.avatar, u.is_verified, u.is_private, (CASE WHEN sv.id IS NOT NULL THEN 1 ELSE 0 END) as is_viewed, (CASE WHEN sr.id IS NOT NULL THEN 1 ELSE 0 END) as is_liked FROM stories s JOIN users u ON s.user_id = u.id LEFT JOIN follows f ON u.id = f.following_id AND f.follower_id = ? LEFT JOIN story_views sv ON s.id = sv.story_id AND sv.user_id = ? LEFT JOIN story_reactions sr ON s.id = sr.story_id AND sr.user_id = ? LEFT JOIN blocks AS ubc ON ubc.blocker_id = ? AND ubc.blocked_entity_id = u.id AND ubc.blocked_type = 'user' LEFT JOIN blocks AS cub ON cub.blocker_id = u.id AND cub.blocked_entity_id = ? AND cub.blocked_type = 'user' WHERE s.expires_at > NOW() AND u.is_banned = 0 AND (f.follower_id IS NOT NULL OR u.id = ?) AND ubc.id IS NULL AND cub.id IS NULL AND (u.is_private = 0 OR (f.follower_id IS NOT NULL) OR u.id = ?) ORDER BY s.created_at ASC"; 
        
        $query = $this->db->query($sql, [ $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId ]); 
        $results = $query->getResultArray(); 
        
        $stories = []; 
        foreach ($results as $row) { 
            $stories[] = [ 
                'id' => (string)$row['id'], 
                'user_id' => (string)$row['user_id'], 
                'media_url' => get_media_url($row['media_url']), 
                'media_type' => $row['media_type'], 
                'duration' => (int)$row['duration'], 
                'caption' => $row['caption'], 
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
    
    /** * ✅ 2. UPLOAD STORY (FFmpeg logic untouched, RCE Hacker Shield Added) 
     */ 
    public function upload() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        if (!$currentUserId) return $this->failUnauthorized(); 
        
        $file = $this->request->getFile('file'); 
        if (!$file || !$file->isValid()) { 
            return $this->fail('Media file required', 400); 
        } 
        
        // 🚨 SECURITY SHIELD: Strict extension & MIME validation
        $allowedImageExts = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedVideoExts = ['mp4', 'mov', 'webm'];
        $allowedImageMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $allowedVideoMimes = ['video/mp4', 'video/quicktime', 'video/webm'];

        $mime = $file->getMimeType(); 
        $ext = strtolower($file->getClientExtension());
        $is_video = (strpos($mime, 'video') !== false); 

        if ($is_video) {
            if (!in_array($mime, $allowedVideoMimes) || !in_array($ext, $allowedVideoExts)) {
                return $this->fail('Invalid video format. Malicious files blocked.', 400);
            }
        } else {
            if (!in_array($mime, $allowedImageMimes) || !in_array($ext, $allowedImageExts)) {
                return $this->fail('Invalid image format. Malicious files blocked.', 400);
            }
        }
        
        $configType = $is_video ? 'story_video' : 'story'; 
        
        // 🔥 FFmpeg & Upload logic unchanged
        $dbPath = upload_media_master($file, $configType); 
        
        if ($dbPath) { 
            $data = [ 
                'user_id' => $currentUserId, 
                'media_url' => $dbPath, 
                'media_type' => $is_video ? 'video' : 'image', 
                'caption' => $this->request->getPost('caption') ?? $this->request->getVar('caption'), 
                'duration' => (int)($this->request->getVar('duration') ?? ($is_video ? 15 : 5)), 
                'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours')), 
                'created_at' => date('Y-m-d H:i:s'), 
                'status' => $is_video ? 'processing' : 'published' 
            ]; 
            $this->db->table('stories')->insert($data); 
            $newStoryId = $this->db->insertID(); 
            
            if ($is_video) { 
                $ffmpegEnabled = $this->db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow(); 
                if ($ffmpegEnabled && $ffmpegEnabled->setting_value === 'true') { 
                    $this->db->table('video_processing_queue')->insert([ 
                        'video_id' => $newStoryId, 
                        'video_type' => 'story', 
                        'input_path' => $dbPath, 
                        'status' => 'pending', 
                        'created_at' => date('Y-m-d H:i:s') 
                    ]); 
                } else { 
                    $this->db->table('stories')->where('id', $newStoryId)->update(['status' => 'published']); 
                } 
            } 
            return $this->respondCreated(['success' => true, 'message' => 'Story uploaded', 'status' => $is_video ? 'processing' : 'published']); 
        } 
        return $this->fail('Upload failed', 500); 
    } 
    
    /** * ✅ 3. DELETE STORY (Fixed DB Bloat & Associated Data Deletion) 
     */ 
    public function deleteStory() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        $storyId = $this->request->getGet('story_id') ?? $this->request->getPost('story_id') ?? $this->request->getVar('story_id'); 
        
        if (!$storyId) { 
            $json = $this->request->getJSON(true); 
            $storyId = $json['story_id'] ?? null; 
        } 
        if (!$storyId) return $this->fail('Story ID required', 400); 
        
        $story = $this->db->table('stories')->where(['id' => $storyId, 'user_id' => $currentUserId])->get()->getRow(); 
        if (!$story) return $this->failNotFound('Story not found or access denied'); 
        
        if ($story->media_url) { 
            delete_media_master($story->media_url); 
        } 
        
        // 🔥 FIX: Clean up associated views and reactions to prevent DB bloat
        $this->db->transStart();
        $this->db->table('story_views')->where('story_id', $storyId)->delete();
        $this->db->table('story_reactions')->where('story_id', $storyId)->delete();
        $this->db->table('stories')->where('id', $storyId)->delete(); 
        $this->db->transComplete();

        return $this->respond(['success' => true, 'message' => 'Story deleted']); 
    } 
    
    /** * ✅ 4. MARK VIEWED (Spam Bot Fix) 
     */ 
    public function markViewed() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        if (!$currentUserId) return $this->failUnauthorized();

        $storyId = $this->request->getGet('story_id') ?? $this->request->getPost('story_id') ?? $this->request->getVar('story_id'); 
        if (!$storyId) return $this->fail('Story ID required', 400); 

        // 🔥 FIX: Verify story exists before counting view
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
    
    /** * ✅ 5. TOGGLE REACTION (Spam Bot Fix) 
     */ 
    public function toggleReaction() { 
        $currentUserId = $this->request->getHeaderLine('User-ID'); 
        if (!$currentUserId) return $this->failUnauthorized();

        $storyId = $this->request->getGet('story_id') ?? $this->request->getPost('story_id') ?? $this->request->getVar('story_id'); 
        if (!$storyId) return $this->fail('Story ID required', 400); 

        // 🔥 FIX: Verify story exists before reacting
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
    
    /** * ✅ 6. GET VIEWERS 
     */ 
    public function getViewers() { 
        $storyId = $this->request->getGet('story_id') ?? $this->request->getPost('story_id') ?? $this->request->getVar('story_id'); 
        if (!$storyId) { 
            $json = $this->request->getJSON(true); 
            $storyId = $json['story_id'] ?? null; 
        } 
        if (!$storyId) { 
            return $this->fail('Story ID required to see viewers.', 400); 
        } 
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

