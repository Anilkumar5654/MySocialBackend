<?php

namespace App\Controllers\Api\Ads;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class EngineController extends BaseController {
    use ResponseTrait;
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        helper(['media', 'url', 'text']);
    }

    /**
     * 🔥 1. FEED MIXER (Main Logic)
     */
    public function inject_into_feed($contentList, $placement) {
        $settings = $this->get_settings_map();
        if (($settings['global_ad_status'] ?? 'active') === 'inactive') return $contentList;
        
        $dbPlacement = $placement; 
        if ($placement === 'reel') $dbPlacement = 'reels'; 
        if ($placement === 'feed') $dbPlacement = 'home'; 

        $switchKey = 'enable_' . $dbPlacement . '_ads'; 
        if (isset($settings[$switchKey]) && $settings[$switchKey] == '0') return $contentList;

        $provider = $settings['active_ad_provider'] ?? 'internal'; 
        $freqKey = 'ad_frequency_' . $dbPlacement; 
        $frequency = (int)($settings[$freqKey] ?? 5); 

        $mixedFeed = [];
        $counter = 0;

        foreach ($contentList as $item) {
            $item['id'] = (string)$item['id']; 
            if(!isset($item['type'])) $item['type'] = $placement;
            $mixedFeed[] = $item;
            $counter++;

            if ($counter % $frequency === 0) {
                $adData = null;
                if ($provider === 'meta') {
                    $adData = $this->format_meta_placeholder($placement, $settings); 
                } 
                elseif ($provider === 'both') {
                    if (rand(0, 1) === 1) {
                        $adData = $this->format_meta_placeholder($placement, $settings); 
                    } else {
                        $ad = $this->fetch_internal_ad($placement, $settings); 
                        if ($ad) $adData = $this->format_ad($ad, $item, $placement); 
                    }
                } 
                else {
                    $ad = $this->fetch_internal_ad($placement, $settings); 
                    if ($ad) $adData = $this->format_ad($ad, $item, $placement); 
                }
                if ($adData) $mixedFeed[] = $adData; 
            }
        }
        return $mixedFeed;
    }

    private function format_meta_placeholder($placement, $settings) {
        $placementId = ($placement === 'reel' || $placement === 'reels') ? ($settings['fb_placement_reels'] ?? '') : ($settings['fb_placement_video'] ?? ''); 
        if (empty($placementId)) return null; 
        return [
            'id' => 'meta_' . uniqid(), 'type' => 'ad_meta', 'is_ad' => true, 'provider' => 'meta', 
            'placement_id' => $placementId, 'fb_app_id' => $settings['fb_app_id'] ?? '', 
            'test_mode' => ($settings['fb_test_mode'] ?? '1') === '1' 
        ];
    }

    /**
     * 🔥 2. INTERNAL AD FETCH
     */
    private function fetch_internal_ad($placement, $settings = []) {
        $now = date('Y-m-d H:i:s'); 
        $request = \Config\Services::request();
        $currentUserId = $request->getHeaderLine('User-ID'); 

        $builder = $this->db->table('ads'); 
        $builder->select('ads.id as ad_record_id, ads.title, ads.cta_label, ads.target_url, ads.ad_type, ads.source_post_id, ads.advertiser_id');
        $builder->select('ads.description, ads.media_url, ads.thumbnail_url');
        
        if ($currentUserId) {
            $capLimit = (int)($settings['internal_ad_cap_limit'] ?? 3); 
            $today = date('Y-m-d');
            
            $subQuery = $this->db->table('ad_logs')
                ->select('ad_id')
                ->where('user_id', $currentUserId)
                ->where('DATE(created_at)', $today)
                ->where('view_count >=', $capLimit) 
                ->get()
                ->getResultArray();

            $excludedIds = array_column($subQuery, 'ad_id');
            if (!empty($excludedIds)) {
                $builder->whereNotIn('ads.id', $excludedIds);
            }
        }

        $builder->select('u_adv.username as advertiser_name, u_adv.avatar as advertiser_avatar');
        $builder->join('users u_adv', 'u_adv.id = ads.advertiser_id', 'left');

        if ($placement === 'video' || $placement === 'feed') {
            $builder->select('v.id as original_video_id, v.title as video_title, v.description as video_desc, v.video_url as video_file, v.thumbnail_url as video_thumb, v.views_count, v.duration, v.created_at as video_date'); 
            $builder->select('u_creator.username as handle, u_creator.name as display_name, u_creator.avatar as user_avatar, u_creator.id as user_id, u_creator.is_verified as user_verified');
            $builder->join('videos v', 'v.id = ads.source_post_id', 'left'); 
            $builder->join('users u_creator', 'u_creator.id = v.user_id', 'left'); 

            if ($currentUserId) {
                $escapedId = $this->db->escape($currentUserId);
                $builder->select("(CASE WHEN (SELECT 1 FROM follows WHERE follower_id = $escapedId AND following_id = v.user_id LIMIT 1) THEN 1 ELSE 0 END) as is_subscribed");
                $builder->select("(SELECT COUNT(*) FROM likes l WHERE l.likeable_id = v.id AND l.likeable_type = 'video' AND l.user_id = $escapedId) as is_liked");
                $builder->select("(SELECT COUNT(*) FROM saves sv WHERE sv.saveable_id = v.id AND sv.saveable_type = 'video' AND sv.user_id = $escapedId) as is_saved");
            } else {
                $builder->select('0 as is_subscribed, 0 as is_liked, 0 as is_saved');
            }
        }
        elseif ($placement === 'reel' || $placement === 'reels') {
            $builder->select('r.id as original_reel_id, r.caption as reel_caption, r.video_url as reel_video, r.thumbnail_url as reel_thumb, r.views_count, r.created_at as reel_date'); 
            $builder->select('u_creator.username as handle, u_creator.name as display_name, u_creator.avatar as user_avatar, u_creator.id as user_id, u_creator.is_verified as user_verified');
            $builder->join('reels r', 'r.id = ads.source_post_id', 'left'); 
            $builder->join('users u_creator', 'u_creator.id = r.user_id', 'left');

            if ($currentUserId) {
                $escapedId = $this->db->escape($currentUserId);
                $builder->select("(CASE WHEN (SELECT 1 FROM follows WHERE follower_id = $escapedId AND following_id = r.user_id LIMIT 1) THEN 1 ELSE 0 END) as is_subscribed");
                $builder->select("(SELECT COUNT(*) FROM likes l WHERE l.likeable_id = r.id AND l.likeable_type = 'reel' AND l.user_id = $escapedId) as is_liked");
            } else {
                $builder->select('0 as is_subscribed, 0 as is_liked, 0 as is_saved');
            }
        }

        $builder->where('ads.status', 'active')->where('ads.spent < ads.budget')->where('ads.placement', $placement)->where('ads.start_date <=', $now); 
        $builder->groupStart()->where('ads.end_date >=', $now)->orWhere('ads.end_date IS NULL')->groupEnd(); 
        
        $builder->orderBy('RAND()')->limit(1); 
        return $builder->get()->getRowArray();
    }

    /**
     * 🔥 3. RAW DATA FOR PLAYER (Updated for Meta Support)
     */
    public function fetch_instream_raw_data() {
        $settings = $this->get_settings_map(); 
        $now = date('Y-m-d H:i:s'); 
        $request = \Config\Services::request();
        $currentUserId = $request->getHeaderLine('User-ID');

        if (($settings['global_ad_status'] ?? 'active') === 'inactive') return null; 
        if (($settings['enable_instream_ads'] ?? '1') == '0') return null; 

        // ✅ Naya Logic: Agar Provider 'meta' hai toh Meta placeholder return karo
        $provider = $settings['active_ad_provider'] ?? 'internal';
        if ($provider === 'meta') {
            return $this->format_meta_placeholder('instream', $settings);
        }

        // Agar provider 'both' ya 'internal' hai, toh internal ads dhundo
        $builder = $this->db->table('ads'); 
        $builder->select('ads.*, users.username as advertiser_name, users.avatar as advertiser_avatar');
        $builder->join('users', 'users.id = ads.advertiser_id', 'left');

        if ($currentUserId) {
            $capLimit = (int)($settings['instream_ad_cap_limit'] ?? 3);
            $today = date('Y-m-d');
            $subQuery = $this->db->table('ad_logs')
                ->select('ad_id')
                ->where('user_id', $currentUserId)
                ->where('DATE(created_at)', $today)
                ->where('view_count >=', $capLimit) 
                ->get()
                ->getResultArray();
            $excludedIds = array_column($subQuery, 'ad_id');
            if (!empty($excludedIds)) { $builder->whereNotIn('ads.id', $excludedIds); }
        }

        $ad = $builder->where('ads.status', 'active')->where('spent < budget')->where('start_date <=', $now)
            ->groupStart()->where('ads.end_date >=', $now)->orWhere('ads.end_date IS NULL')->groupEnd() 
            ->where('placement', 'instream')->where('ad_type', 'custom_ad')->where('media_type', 'video')
            ->orderBy('RAND()')->limit(1)->get()->getRow(); 
            
        if ($ad) {
            return [
                'id' => (int)$ad->id, 'type' => 'ad_instream', 'title' => $ad->title, 'media_url' => get_media_url($ad->media_url), 
                'thumbnail_url' => get_media_url($ad->thumbnail_url), 'cta_label' => $ad->cta_label ?? 'Learn More', 
                'click_action' => 'open_browser', 'target_url' => $ad->target_url, 'username' => $ad->advertiser_name ?? 'Sponsored',
                'avatar' => get_media_url($ad->advertiser_avatar), 'skip_enabled' => ($settings['instream_skip_enabled'] ?? '1') == '1',
                'skip_after' => (int)($settings['instream_skip_seconds'] ?? 5), 'campaign_id' => $ad->id 
            ];
        }

        // Agar 'both' mode hai aur internal ad nahi mila, toh fallback to Meta
        if ($provider === 'both') {
            return $this->format_meta_placeholder('instream', $settings);
        }

        return null; 
    }

    private function format_ad($ad, $lastItem = null, $placement = 'feed') {
        if ($ad['ad_type'] === 'boosted_content' && !empty($ad['source_post_id'])) {
            $nativeData = [
                'ad_record_id' => (string)$ad['ad_record_id'], 'is_boosted' => true, 'is_ad' => true, 'is_external' => false,
                'target_url' => $ad['target_url'] ?? ("mysocial://post/" . $ad['source_post_id']),
                'cta_label' => $ad['cta_label'] ?? 'Watch Now', 
                'display_name' => $ad['display_name'] ?? $ad['advertiser_name'], 
                'handle' => $ad['handle'] ?? $ad['advertiser_name'], 'user_id' => $ad['user_id'],
                'user_avatar' => get_media_url($ad['user_avatar'] ?? $ad['advertiser_avatar'], 'profile'), 
                'user_verified' => (string)($ad['user_verified'] ?? '0'),
                'is_liked' => (bool)($ad['is_liked'] ?? false), 'is_saved' => (bool)($ad['is_saved'] ?? false), 'is_subscribed' => (bool)($ad['is_subscribed'] ?? false),
                'advertiser_id' => $ad['advertiser_id'],
                'advertiser_name' => $ad['advertiser_name'],
                'advertiser_avatar' => get_media_url($ad['advertiser_avatar'], 'profile'), 
                'user' => [
                    'id' => (string)$ad['user_id'],
                    'username' => $ad['handle'] ?? $ad['advertiser_name'],
                    'name' => $ad['display_name'] ?? $ad['advertiser_name'],
                    'avatar' => get_media_url($ad['user_avatar'] ?? $ad['advertiser_avatar'], 'profile'),
                    'is_verified' => (string)($ad['user_verified'] ?? '0')
                ]
            ];
            
            if ($placement === 'reel' || $placement === 'reels') {
                return array_merge($nativeData, [
                    'id' => (string)$ad['original_reel_id'], 'type' => 'reel', 'video_url' => get_media_url($ad['reel_video']),
                    'thumbnail_url' => get_media_url($ad['reel_thumb']), 'caption' => $ad['reel_caption'], 'views_count' => (int)($ad['views_count'] ?? 0), 'created_at' => $ad['reel_date'], 
                ]);
            } 
            else {
                return array_merge($nativeData, [
                    'id' => (string)$ad['original_video_id'], 'type' => 'video', 'title' => $ad['video_title'], 'description' => $ad['video_desc'] ?? '',
                    'video_url' => get_media_url($ad['video_file']), 'thumbnail_url' => get_media_url($ad['video_thumb']), 'views_count' => (int)($ad['views_count'] ?? 0),
                    'duration' => (string)($ad['duration'] ?? '0'), 'created_at' => $ad['video_date'], 
                ]);
            }
        }
        
        return [
            'id' => 'ad_' . (string)$ad['ad_record_id'] . '_' . uniqid(), 'ad_record_id' => (string)$ad['ad_record_id'], 'type' => 'ad', 'is_ad' => true,
            'is_external' => true, 'is_boosted' => false, 'campaign_id' => $ad['ad_record_id'], 'title' => $ad['title'], 'description' => $ad['description'] ?? 'Sponsored', 
            'media_url' => get_media_url($ad['media_url']), 'thumbnail_url' => get_media_url($ad['thumbnail_url']), 'target_url' => $ad['target_url'], 'cta_label' => $ad['cta_label'] ?? 'Learn More', 
            'username' => $ad['advertiser_name'] ?? 'Sponsored', 
            'avatar' => get_media_url($ad['advertiser_avatar'] ?? null), 
            'advertiser_avatar' => get_media_url($ad['advertiser_avatar'] ?? null),
            'creator_id' => (string)($lastItem['channel_id'] ?? $lastItem['user_id'] ?? 0) 
        ];
    }

    private function get_settings_map() {
        $settings = [];
        $tables = ['ad_settings', 'ad_network_settings'];
        foreach ($tables as $table) {
            $query = $this->db->table($table)->get()->getResultArray();
            foreach ($query as $row) { $settings[$row['setting_key']] = $row['setting_value']; }
        }
        return $settings; 
    }

    public function get_instream_ad() {
        $adData = $this->fetch_instream_raw_data(); 
        if ($adData) return $this->respond(['status' => true, 'data' => ['ad' => $adData]]); 
        return $this->failNotFound('No ads available'); 
    }
}
