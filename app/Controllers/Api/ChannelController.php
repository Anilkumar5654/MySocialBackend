<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\ChannelModel;
use App\Models\VideoModel;
use App\Models\ReelModel;

class ChannelController extends BaseController
{
    use ResponseTrait;

    protected $channelModel;
    protected $videoModel;
    protected $reelModel;
    protected $db;

    public function __construct()
    {
        $this->channelModel = new ChannelModel();
        $this->videoModel   = new VideoModel(); 
        $this->reelModel    = new ReelModel();
        $this->db           = \Config\Database::connect();
        
        helper(['media', 'url', 'text', 'filesystem']); 
    }

    /**
     * ✅ CHECK USER CHANNEL 
     * Kaam: Ye check karta hai ki user ka channel pehle se hai ya nahi 
     * aur uske followers count ko users table se uthata hai.
     */
    public function checkUserChannel($userId = null)
    {
        $targetUserId = $userId ?? ($this->request->getGet('user_id') ?? $this->request->getHeaderLine('User-ID'));
        
        if (!$targetUserId) return $this->fail('User ID is required');

        $builder = $this->db->table('channels c');
        $builder->select('c.*, u.followers_count');
        $builder->join('users u', 'u.id = c.user_id');
        $builder->where('c.user_id', $targetUserId);
        
        $channel = $builder->get()->getRowArray();
        
        if ($channel) {
            $channel['followers_count'] = (int)$channel['followers_count'];
            $channel['subscribers_count'] = $channel['followers_count'];
            $channel['bio'] = $channel['description'];
        }

        return $this->respond([
            'success' => true,
            'has_channel' => !empty($channel),
            'channel' => $channel 
        ]); 
    }
}
