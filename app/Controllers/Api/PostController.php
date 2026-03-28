<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Controllers\Api\Ads\EngineController;
use App\Helpers\HashtagHelper;

class PostController extends BaseController
{
    use ResponseTrait;
    protected $db;
    protected $hashtagHelper;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['media', 'url', 'filesystem', 'Content/viral', 'text', 'session']);
        $this->hashtagHelper = new HashtagHelper();
    }

    /**
     * 🔥 NORMALIZER - Mobile formatting
     */
    private function normalizeKeys(array $item): array
    {
        $boolKeys = ['is_liked', 'is_saved', 'is_following', 'is_followed_by_viewer', 'is_verified'];
        foreach ($boolKeys as $key) {
            if (array_key_exists($key, $item)) {
                $item[$key] = (bool)$item[$key];
            }
        }
        
        if (array_key_exists('created_at', $item)) {
            if (empty($item['created_at']) || $item['created_at'] == '0000-00-00 00:00:00') {
                $item['created_at'] = date('Y-m-d H:i:s');
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
     * ✅ 1. GET FEED (Aggressive Freshness Logic + Music Integration)
     */
    public function getFeed()
    {
        $session = \Config\Services::session();
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $escapedUserId = $this->db->escape($currentUserId);
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 15;
        $offset = ($page - 1) * $limit;
        
        $feedType = $this->request->getGet('type') ?? 'for-you';

        if ($page == 1) {
            $seed = (int)(microtime(true) * 1000);
            $session->set('feed_seed', $seed);
        } else {
            $seed = $session->get('feed_seed') ?: (int)(microtime(true) * 1000);
        }

        $builder = $this->db->table('posts p');
        // 🔥 MODIFIED: Added music_id and joined music table to fetch audio details
        $builder->select('p.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified, c.id as channel_id, m.title as music_title, m.artist as music_artist, m.audio_url as music_url, m.cover_url as music_cover');
        $builder->join('users u', 'p.user_id = u.id');
        $builder->join('channels c', 'c.user_id = u.id', 'left');
        $builder->join('music m', 'm.id = p.music_id', 'left'); // Left join to allow posts without music
        $builder->where('p.status', 'published');

        if ($currentUserId > 0) {
            $builder->whereNotIn('p.user_id', function ($subquery) use ($currentUserId) {
                return $subquery->select('blocked_entity_id')->from('blocks')->where('blocker_id', $currentUserId);
            });
            $builder->select("(SELECT COUNT(*) FROM likes WHERE likeable_id = p.id AND likeable_type = 'post' AND user_id = {$escapedUserId}) as is_liked");
            $builder->select("(SELECT COUNT(*) FROM saves WHERE saveable_id = p.id AND saveable_type = 'post' AND user_id = {$escapedUserId}) as is_saved");
            $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = {$escapedUserId} AND following_id = p.user_id) as is_following");
        }

        if ($feedType === 'following' && $currentUserId > 0) {
            $builder->whereIn('p.user_id', function ($subquery) use ($currentUserId) {
                return $subquery->select('following_id')->from('follows')->where('follower_id', $currentUserId);
            });
            $builder->orderBy('p.created_at', 'DESC'); 
        } 
        else if ($feedType === 'recent') {
            $builder->where('p.feed_scope', 'public');
            $builder->orderBy('p.created_at', 'DESC');
        }
        else if ($feedType === 'trending') {
            $builder->where('p.feed_scope', 'public');
            $builder->orderBy('p.views_count', 'DESC');
            $builder->orderBy('p.created_at', 'DESC');
        }
        else {
            $builder->where('p.feed_scope', 'public');
            $builder->orderBy("CASE WHEN p.created_at >= NOW() - INTERVAL 3 DAY THEN 1 ELSE 2 END", "ASC");
            $builder->orderBy("RAND($seed)"); 
        }

        $postsData = $builder->get($limit, $offset)->getResultArray();

        foreach ($postsData as &$row) {
            $media = $this->db->table('post_media')->select('media_url')->where('post_id', $row['id'])->orderBy('display_order', 'ASC')->get()->getResultArray();
            $row['id'] = (string)$row['id'];
            $row['type'] = 'post';
            $row['images'] = array_map(fn($img) => get_media_url($img['media_url']), $media);
            $row['user'] = [
                "id" => (string)$row['user_id'],
                "username" => $row['handle'],
                "name" => $row['display_name'],
                "avatar" => get_media_url($row['user_avatar']),
                "is_verified" => $row['is_verified'],
                "is_following" => $row['is_following'] ?? 0
            ];

            // 🔥 MODIFIED: Added structured music object to API response
            if (!empty($row['music_id'])) {
                $row['music'] = [
                    "id" => (string)$row['music_id'],
                    "title" => $row['music_title'],
                    "artist" => $row['music_artist'],
                    "audio_url" => get_media_url($row['music_url'], 'audio'),
                    "cover_url" => get_media_url($row['music_cover'], 'image')
                ];
            } else {
                $row['music'] = null; // No music selected
            }

            // Cleanup raw joined music fields to keep API clean
            unset($row['music_title'], $row['music_artist'], $row['music_url'], $row['music_cover']);

            $row = $this->normalizeKeys($row);
        }

        $reelsData = $this->db->table('reels r')
            ->select('r.*, u.username as handle, u.avatar as user_avatar, u.id as user_id')
            ->join('users u', 'r.user_id = u.id')
            ->where(['r.status' => 'published', 'r.visibility' => 'public'])
            ->orderBy("RAND($seed)")
            ->limit(5)
            ->get()->getResultArray();

        foreach ($reelsData as &$rData) {
            $rData['thumbnail_url'] = get_media_url($rData['thumbnail_url']);
            $rData['video_url'] = get_media_url($rData['video_url']);
            $rData['type'] = 'reel_item';
            $rData['user'] = ["id" => (string)$rData['user_id'], "username" => $rData['handle'], "avatar" => get_media_url($rData['user_avatar'])];
            $rData = $this->normalizeKeys($rData);
        }

        $longVideosData = $this->db->table('videos v')
            ->select('v.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified')
            ->join('users u', 'v.user_id = u.id')
            ->where(['v.status' => 'published', 'v.visibility' => 'public'])
            ->orderBy("RAND($seed)")
            ->limit(5)
            ->get()->getResultArray();

        foreach ($longVideosData as &$vData) {
            $vData['id'] = (string)$vData['id'];
            $vData['type'] = 'video'; 
            $vData['thumbnail_url'] = get_media_url($vData['thumbnail_url']);
            $vData['video_url'] = get_media_url($vData['video_url']);
            $vData['user'] = [
                "id" => (string)$vData['user_id'],
                "username" => $vData['handle'],
                "name" => $vData['display_name'],
                "avatar" => get_media_url($vData['user_avatar']),
                "is_verified" => $vData['is_verified']
            ];
            $vData = $this->normalizeKeys($vData);
        }

        $mixedFeed = [];
        $reelIndex = 0;
        $videoIndex = 0;

        foreach ($postsData as $index => $post) {
            $mixedFeed[] = $post;
            if (($index + 1) % 3 == 0 && isset($reelsData[$reelIndex])) {
                $mixedFeed[] = $reelsData[$reelIndex];
                $reelIndex++;
            }
            if (($index + 1) % 5 == 0 && isset($longVideosData[$videoIndex])) {
                $mixedFeed[] = $longVideosData[$videoIndex];
                $videoIndex++;
            }
        }

        $adEngine = new EngineController();
        $injected = $adEngine->inject_into_feed($mixedFeed, 'feed');

        return $this->respond(["success" => true, "posts" => $injected, "hasMore" => count($postsData) === $limit]);
    }

    /**
     * ✅ 2. GET DETAILS (Added Music Details + Follow/Like/Save State)
     */
    public function getDetails()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $escapedUserId = $this->db->escape($currentUserId);
        
        $postId = $this->request->getGet('post_id') ?? $this->request->getVar('post_id');

        if (!$postId) return $this->fail('Post ID required.', 400);

        $builder = $this->db->table('posts p');
        
        // 🔥 MODIFIED: Joining music table here as well
        $builder->select('p.*, u.username as handle, u.avatar as user_avatar, u.id as user_id, u.is_verified, m.title as music_title, m.artist as music_artist, m.audio_url as music_url, m.cover_url as music_cover');
        
        if ($currentUserId > 0) {
            $builder->select("(SELECT COUNT(*) FROM likes WHERE likeable_id = p.id AND likeable_type = 'post' AND user_id = {$escapedUserId}) as is_liked");
            $builder->select("(SELECT COUNT(*) FROM saves WHERE saveable_id = p.id AND saveable_type = 'post' AND user_id = {$escapedUserId}) as is_saved");
            $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = {$escapedUserId} AND following_id = p.user_id) as is_following");
        }

        $builder->join('users u', 'p.user_id = u.id');
        $builder->join('music m', 'm.id = p.music_id', 'left');
        $builder->groupStart()->where('p.id', $postId)->orWhere('p.unique_id', $postId)->groupEnd();

        $post = $builder->get()->getRowArray();
        if (!$post) return $this->failNotFound();

        $media = $this->db->table('post_media')->where('post_id', $post['id'])->orderBy('display_order', 'ASC')->get()->getResultArray();
        $post['images'] = array_map(fn($img) => get_media_url($img['media_url']), $media);
        
        $post['user'] = [
            "id" => (string)$post['user_id'], 
            "username" => $post['handle'], 
            "avatar" => get_media_url($post['user_avatar']), 
            "is_verified" => $post['is_verified'],
            "is_following" => $post['is_following'] ?? 0
        ];

        // 🔥 MODIFIED: Music formatting
        if (!empty($post['music_id'])) {
            $post['music'] = [
                "id" => (string)$post['music_id'],
                "title" => $post['music_title'],
                "artist" => $post['music_artist'],
                "audio_url" => get_media_url($post['music_url'], 'audio'),
                "cover_url" => get_media_url($post['music_cover'], 'image')
            ];
        } else {
            $post['music'] = null;
        }
        unset($post['music_title'], $post['music_artist'], $post['music_url'], $post['music_cover']);

        return $this->respond(['success' => true, 'post' => $this->normalizeKeys($post)]);
    }

    /**
     * ✅ 3. CREATE POST (Added music_id support & trend tracking)
     */
    public function create()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized();

        $files = $this->request->getFileMultiple('images');
        $content = $this->request->getPost('caption');
        $musicId = $this->request->getPost('music_id') ?: null; // 🔥 Fetching music ID from app

        $this->db->transStart();
        $uniqueId = 'PST' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));

        $postData = [
            'user_id' => $currentUserId,
            'unique_id' => $uniqueId,
            'content' => trim($content),
            'aspect_ratio' => $this->request->getVar('aspect_ratio') ?: 1.00,
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s')
        ];

        // 🔥 MODIFIED: Save music_id if provided
        if (!empty($musicId)) {
            $postData['music_id'] = $musicId;
            // Increase usage_count for trending songs logic
            $this->db->query("UPDATE music SET usage_count = usage_count + 1 WHERE id = ?", [$musicId]);
        }

        $this->db->table('posts')->insert($postData);

        $postId = $this->db->insertID();
        $this->db->table('users')->where('id', $currentUserId)->increment('posts_count');
        if (!empty($content)) $this->hashtagHelper->syncHashtags($postId, 'post', $content);

        if ($files) {
            foreach ($files as $index => $file) {
                if ($file->isValid()) {
                    $dbPath = upload_media_master($file, 'post_img');
                    $this->db->table('post_media')->insert(['post_id' => $postId, 'media_url' => $dbPath, 'display_order' => $index]);
                }
            }
        }
        $this->db->transComplete();
        return $this->respondCreated(['success' => true, 'post_id' => (string)$postId, 'unique_id' => $uniqueId]);
    }

    /**
     * ✅ 4. DELETE POST (Handling Music Count decrement)
     */
    public function delete($id = null)
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        $postId = $id ?? $this->request->getVar('id');

        $post = $this->db->table('posts')->where('id', $postId)->orWhere('unique_id', $postId)->get()->getRowArray();
        if (!$post || $post['user_id'] != $currentUserId) return $this->failForbidden();

        $this->db->transStart();
        
        // 🔥 MODIFIED: Decrease music usage count if post is deleted
        if (!empty($post['music_id'])) {
            $this->db->query("UPDATE music SET usage_count = GREATEST(usage_count - 1, 0) WHERE id = ?", [$post['music_id']]);
        }

        $this->db->table('users')->where('id', $currentUserId)->decrement('posts_count');
        $this->db->table('post_media')->where('post_id', $post['id'])->delete();
        $this->db->table('posts')->where('id', $post['id'])->delete();
        $this->db->transComplete();

        return $this->respond(['success' => true]);
    }

    /**
     * ✅ 5. GET EXPLORE FEED (Added Music Details + Follow/Like/Save State)
     */
    public function getExploreFeed()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        $escapedUserId = $this->db->escape($currentUserId);

        $userId = $this->request->getGet('user_id');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $builder = $this->db->table('posts p');
        $builder->select('p.*, u.username as handle, u.name as display_name, u.avatar as user_avatar, u.id as user_id, u.is_verified, m.title as music_title, m.artist as music_artist, m.audio_url as music_url, m.cover_url as music_cover');
        $builder->join('users u', 'p.user_id = u.id');
        $builder->join('music m', 'm.id = p.music_id', 'left'); // 🔥 MODIFIED
        $builder->where(['p.user_id' => $userId, 'p.status' => 'published']);

        if ($currentUserId > 0) {
            $builder->select("(SELECT COUNT(*) FROM likes WHERE likeable_id = p.id AND likeable_type = 'post' AND user_id = {$escapedUserId}) as is_liked");
            $builder->select("(SELECT COUNT(*) FROM saves WHERE saveable_id = p.id AND saveable_type = 'post' AND user_id = {$escapedUserId}) as is_saved");
            $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = {$escapedUserId} AND following_id = p.user_id) as is_following");
        }

        $builder->orderBy('p.created_at', 'DESC');
        $posts = $builder->get($limit, $offset)->getResultArray();

        foreach ($posts as &$row) {
            $media = $this->db->table('post_media')->select('media_url')->where('post_id', $row['id'])->orderBy('display_order', 'ASC')->get()->getResultArray();
            $row['images'] = array_map(fn($img) => get_media_url($img['media_url']), $media);
            
            $row['user'] = [
                "id" => (string)$row['user_id'],
                "username" => $row['handle'],
                "name" => $row['display_name'],
                "avatar" => get_media_url($row['user_avatar']),
                "is_verified" => $row['is_verified'],
                "is_following" => $row['is_following'] ?? 0
            ];

            // 🔥 MODIFIED: Music formatting
            if (!empty($row['music_id'])) {
                $row['music'] = [
                    "id" => (string)$row['music_id'],
                    "title" => $row['music_title'],
                    "artist" => $row['music_artist'],
                    "audio_url" => get_media_url($row['music_url'], 'audio'),
                    "cover_url" => get_media_url($row['music_cover'], 'image')
                ];
            } else {
                $row['music'] = null;
            }
            unset($row['music_title'], $row['music_artist'], $row['music_url'], $row['music_cover']);

            $row = $this->normalizeKeys($row);
        }

        return $this->respond(["success" => true, "posts" => $posts, "hasMore" => count($posts) === $limit]);
    }
}
