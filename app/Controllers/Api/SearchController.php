<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class SearchController extends BaseController {
    use ResponseTrait;
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        helper(['media', 'url']);
    }

    public function index() {
        $query = trim((string)$this->request->getGet('q'));
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: 0;
        
        if (empty($query)) {
            return $this->respond(['success' => true, 'results' => $this->emptyResults()]);
        }

        return $this->respond([
            'success' => true,
            'results' => [
                'accounts' => $this->searchAccounts($query, $currentUserId),
                'videos'   => $this->searchVideos($query),
                'reels'    => $this->searchReels($query),
                'tags'     => $this->searchTags($query)
            ]
        ]);
    }

    /** 1. Accounts Search (Users + Channels) **/
    private function searchAccounts($q, $uid) {
        return $this->db->table('users u')
            ->select('u.id, u.username, u.name, u.avatar, u.is_verified, u.followers_count')
            ->select("(SELECT COUNT(*) FROM follows WHERE follower_id = $uid AND following_id = u.id) as is_following")
            ->groupStart()
                ->like('u.username', $q)
                ->orLike('u.name', $q)
            ->groupEnd()
            ->where('u.is_deleted', 0)
            ->limit(15)
            ->get()->getResultArray();
    }

    /** 2. Videos Search **/
    private function searchVideos($q) {
        $results = $this->db->table('videos v')
            ->select('v.id, v.unique_id, v.title, v.thumbnail_url, v.views_count, v.duration, v.created_at, u.username as channel_name, u.avatar as channel_avatar')
            ->join('users u', 'u.id = v.user_id')
            ->like('v.title', $q)
            ->where('v.status', 'published')
            ->where('v.visibility', 'public')
            ->limit(10)
            ->get()->getResultArray();

        foreach($results as &$v) {
            $v['thumbnail_url'] = get_media_url($v['thumbnail_url']);
            $v['channel_avatar'] = get_media_url($v['channel_avatar']);
        }
        return $results;
    }

    /** 3. Reels Search **/
    private function searchReels($q) {
        $results = $this->db->table('reels r')
            ->select('r.id, r.unique_id, r.thumbnail_url, r.views_count, r.caption')
            ->like('r.caption', $q)
            ->where('r.status', 'published')
            ->where('r.visibility', 'public')
            ->limit(18)
            ->get()->getResultArray();

        foreach($results as &$r) {
            $r['thumbnail_url'] = get_media_url($r['thumbnail_url']);
        }
        return $results;
    }

    /** 4. Hashtags Search **/
    private function searchTags($q) {
        $q = str_replace('#', '', $q); // Remove # if user typed it
        return $this->db->table('hashtags')
            ->select('id, tag, posts_count as count')
            ->like('tag', $q)
            ->orderBy('posts_count', 'DESC')
            ->limit(15)
            ->get()->getResultArray();
    }

    private function emptyResults() {
        return ['accounts' => [], 'videos' => [], 'reels' => [], 'tags' => []];
    }
}
