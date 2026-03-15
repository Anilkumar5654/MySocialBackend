<?php

namespace App\Controllers\Api\System;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\HashtagModel;

class HashtagController extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        
        // 🔥 IMPORT MEDIA HELPER (Taaki get_media_url use kar sakein)
        helper(['media', 'url', 'text']);
    }

    /**
     * ✅ FEED: Fully Synchronized with Media Helper
     */
    public function feed($tag = null)
    {
        $inputTag = $tag ?? $this->request->getGet('tag');
        if (!$inputTag) return $this->failNotFound('Hashtag required');
        
        $cleanTag = strtolower(trim(ltrim(urldecode($inputTag), '#')));

        // 1. Tag ID nikalo
        $tagRow = $this->db->table('hashtags')->select('id, tag')->where('tag', $cleanTag)->get()->getRow();

        if (!$tagRow) {
            return $this->respond([
                'success' => true,
                'meta' => ['tag' => '#' . $cleanTag, 'total_posts' => 0, 'filter' => 'all'],
                'items' => [],
                'hasMore' => false
            ]);
        }

        $tagId = $tagRow->id;
        $realTagName = $tagRow->tag;

        // Filters
        $filter = $this->request->getGet('filter') ?? 'top'; 
        $page   = (int)($this->request->getGet('page') ?? 1);
        $limit  = 15;
        $offset = ($page - 1) * $limit;

        // 2. Optimized SQL Queries (Raw columns select kar rahe hain, formatting loop me helper karega)
        
        // POSTS
        $postsQuery = "SELECT p.id, p.user_id, p.content as title, 'post' as type, 
                              p.viral_score, p.views_count as views, p.likes_count as likes, 
                              0 as duration, p.created_at, 
                              (SELECT media_url FROM post_media WHERE post_id = p.id ORDER BY display_order ASC LIMIT 1) as thumb,
                              u.username as owner_name, u.avatar as owner_avatar, u.is_verified,
                              NULL as channel_name, NULL as channel_avatar
                       FROM posts p
                       JOIN taggables t ON t.taggable_id = p.id AND t.taggable_type = 'post'
                       JOIN users u ON u.id = p.user_id
                       WHERE t.hashtag_id = ? AND p.status = 'published'";

        // REELS
        $reelsQuery = "SELECT r.id, r.user_id, r.caption as title, 'reel' as type, 
                              r.viral_score, r.views_count as views, r.likes_count as likes, 
                              r.duration, r.created_at, r.thumbnail_url as thumb,
                              u.username as owner_name, u.avatar as owner_avatar, u.is_verified,
                              c.name as channel_name, c.avatar as channel_avatar
                       FROM reels r
                       JOIN taggables t ON t.taggable_id = r.id AND t.taggable_type = 'reel'
                       JOIN users u ON u.id = r.user_id
                       LEFT JOIN channels c ON c.id = r.channel_id
                       WHERE t.hashtag_id = ? AND r.status = 'published'";

        // VIDEOS
        $videosQuery = "SELECT v.id, v.user_id, v.title, 'video' as type, 
                               v.viral_score, v.views_count as views, v.likes_count as likes, 
                               v.duration, v.created_at, v.thumbnail_url as thumb,
                               u.username as owner_name, u.avatar as owner_avatar, u.is_verified,
                               c.name as channel_name, c.avatar as channel_avatar
                        FROM videos v
                        JOIN taggables t ON t.taggable_id = v.id AND t.taggable_type = 'video'
                        JOIN users u ON u.id = v.user_id
                        LEFT JOIN channels c ON c.id = v.channel_id
                        WHERE t.hashtag_id = ? AND v.status = 'published'";

        // Filter Switch
        $sqlParts = [];
        $bindings = [];

        if ($filter === 'reels') {
            $sqlParts[] = $reelsQuery; $bindings[] = $tagId;
        } elseif ($filter === 'videos') {
            $sqlParts[] = $videosQuery; $bindings[] = $tagId;
        } else {
            $sqlParts[] = $postsQuery; $bindings[] = $tagId;
            $sqlParts[] = $reelsQuery; $bindings[] = $tagId;
        }

        $mainSql = implode(" UNION ALL ", $sqlParts);
        $orderBy = ($filter === 'recent') ? "ORDER BY created_at DESC" : "ORDER BY viral_score DESC, created_at DESC";

        // Count
        $countSql = "SELECT COUNT(*) as total FROM ($mainSql) as temp_table";
        $totalQuery = $this->db->query($countSql, $bindings);
        $totalPosts = $totalQuery->getRow()->total ?? 0;

        // Data Fetch
        $bindings[] = $limit;
        $bindings[] = $offset;
        $query = $this->db->query("$mainSql $orderBy LIMIT ? OFFSET ?", $bindings);
        $results = $query->getResultArray();

        // 3. 🔥 FORMATTING WITH MEDIA HELPER
        foreach ($results as &$item) {
            $item['id'] = (string)$item['id'];

            // ✅ Use Helper for Thumbnail (Handles null, placeholders, full URL)
            $item['thumb'] = get_media_url($item['thumb']); 
            
            $item['views']    = (int)$item['views'];
            $item['likes']    = (int)$item['likes'];
            $item['duration'] = (int)$item['duration'];

            // Determine Channel vs User (Priority to Channel)
            $isChannelContent = !empty($item['channel_name']);
            
            $displayName   = $isChannelContent ? $item['channel_name'] : $item['owner_name'];
            $rawAvatarPath = $isChannelContent ? $item['channel_avatar'] : $item['owner_avatar'];

            // ✅ Use Helper for Avatar
            $displayAvatar = get_media_url($rawAvatarPath);

            // Structure for Frontend
            $item['channel'] = [
                'name'        => $displayName,
                'avatar'      => $displayAvatar, // Helper ne URL bana diya
                'is_verified' => (bool)($item['is_verified'] ?? false)
            ];

            // User fallback
            $item['user'] = [
                'username' => $item['owner_name'] ?? 'Unknown',
                'avatar'   => get_media_url($item['owner_avatar'] ?? null)
            ];

            // Cleanup raw fields
            unset($item['owner_name'], $item['owner_avatar'], $item['channel_name'], $item['channel_avatar'], $item['is_verified']);
        }

        return $this->respond([
            'success' => true,
            'meta'    => [
                'tag' => '#' . $realTagName,
                'total_posts' => (int)$totalPosts,
                'filter' => $filter,
            ],
            'items'   => $results,
            'hasMore' => count($results) === $limit
        ]);
    }

    public function trending()
    {
        $limit = (int)($this->request->getGet('limit') ?? 10);
        $query = $this->request->getGet('q');
        $model = new HashtagModel();
        return $this->respond(['success' => true, 'hashtags' => $model->getTrending($limit, $query)]);
    }

    /**
     * ✅ SEARCH: Pop-up suggestion ke liye viral tags return karega
     * Jab user post create karte waqt # type karega tab ye use hoga.
     */
    public function search()
    {
        $query = $this->request->getGet('q');
        $limit = 10;

        $builder = $this->db->table('hashtags');
        $builder->select('tag, posts_count');

        if (!empty($query)) {
            $cleanQuery = ltrim($query, '#');
            $builder->like('tag', $cleanQuery, 'after');
        }

        // Sabse zyada posts wale tags pehle dikhao (Viral Logic)
        $builder->orderBy('posts_count', 'DESC');
        $builder->limit($limit);

        $results = $builder->get()->getResultArray();

        return $this->respond([
            'success' => true,
            'items'   => $results
        ]);
    }
}
