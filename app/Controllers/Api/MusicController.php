<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class MusicController extends BaseController
{
    use ResponseTrait;
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Load helpers to format media URLs
        helper(['url', 'media']);
    }

    /**
     * 🔥 FORMATTER - Format Music Data for App
     */
    private function formatMusicData(array $musicList): array
    {
        foreach ($musicList as &$song) {
            $song['id'] = (string)$song['id'];
            $song['audio_url'] = get_media_url($song['audio_url'], 'audio');
            $song['cover_url'] = get_media_url($song['cover_url'], 'image');
            $song['usage_count'] = (int)$song['usage_count'];
            $song['duration'] = (int)$song['duration'];
        }
        return $musicList;
    }

    /**
     * ✅ 1. GET TRENDING MUSIC (For Create Post/Story Modal)
     */
    public function getTrending()
    {
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $builder = $this->db->table('music');
        $builder->orderBy('usage_count', 'DESC'); // Sabse zyada use hone wale gaane upar
        $builder->orderBy('created_at', 'DESC');

        $musicList = $builder->get($limit, $offset)->getResultArray();

        $formattedMusic = $this->formatMusicData($musicList);

        return $this->respond([
            'success' => true,
            'music'   => $formattedMusic,
            'hasMore' => count($musicList) === $limit
        ]);
    }

    /**
     * ✅ 2. SEARCH MUSIC (User searches for a specific song or artist)
     */
    public function search()
    {
        $query = trim($this->request->getGet('q') ?? '');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        if (empty($query)) {
            return $this->fail('Search query is required.', 400);
        }

        $builder = $this->db->table('music');
        $builder->groupStart()
                ->like('title', $query)
                ->orLike('artist', $query)
                ->groupEnd();
        
        $builder->orderBy('usage_count', 'DESC'); // Match hone par popular gaane pehle
        
        $musicList = $builder->get($limit, $offset)->getResultArray();

        $formattedMusic = $this->formatMusicData($musicList);

        return $this->respond([
            'success' => true,
            'music'   => $formattedMusic,
            'hasMore' => count($musicList) === $limit
        ]);
    }

    /**
     * ✅ 3. GET SINGLE MUSIC DETAILS (Optional: if needed for specific deep linking)
     */
    public function getDetails()
    {
        $musicId = $this->request->getGet('id');

        if (!$musicId) {
            return $this->fail('Music ID is required.', 400);
        }

        $song = $this->db->table('music')->where('id', $musicId)->get()->getRowArray();

        if (!$song) {
            return $this->failNotFound('Music not found.');
        }

        $formattedMusic = $this->formatMusicData([$song]);

        return $this->respond([
            'success' => true,
            'music'   => $formattedMusic[0]
        ]);
    }
}
