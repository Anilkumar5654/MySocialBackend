<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SystemSync extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'system:sync';
    protected $description = 'Comprehensive Sync: Fixes all counters for Users, Channels, Hashtags, Views, and Impressions.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        CLI::write("🚀 Starting Global System Sync (Deep Audit Mode)...", 'yellow');

        // --- 1. SYNC HASHTAG COUNTS ---
        CLI::write("Updating Hashtag usage counts...", 'cyan');
        $db->query("
            UPDATE hashtags h 
            SET h.posts_count = (
                SELECT COUNT(*) FROM taggables t 
                WHERE t.hashtag_id = h.id AND t.is_visible = 1
            )
        ");

        // --- 2. SYNC VIDEO & REEL VIEWS/IMPRESSIONS (REAL-TIME SYNC) ---
        CLI::write("Syncing Views and Impressions for Videos & Reels...", 'cyan');
        
        // Sync Videos: Views & Impressions
        $db->query("
            UPDATE videos v 
            SET 
                v.views_count = (SELECT COUNT(*) FROM views WHERE viewable_id = v.id AND viewable_type = 'video'),
                v.impressions_count = (SELECT COUNT(*) FROM impressions WHERE impressionable_id = v.id AND impressionable_type = 'video')
        ");

        // Sync Reels: Views & Impressions
        $db->query("
            UPDATE reels r 
            SET 
                r.views_count = (SELECT COUNT(*) FROM views WHERE viewable_id = r.id AND viewable_type = 'reel'),
                r.impressions_count = (SELECT COUNT(*) FROM impressions WHERE impressionable_id = r.id AND impressionable_type = 'reel')
        ");

        // --- 3. SYNC CHANNEL METRICS ---
        CLI::write("Syncing Channel Videos & Strike counts...", 'cyan');
        $channels = $db->table('channels')->get()->getResult();
        foreach ($channels as $channel) {
            $cUpdates = [];
            
            $vCount = $db->table('videos')->where(['channel_id' => $channel->id, 'status' => 'published'])->countAllResults();
            if ((int)$channel->videos_count !== $vCount) $cUpdates['videos_count'] = $vCount;

            $sCount = $db->table('channel_strikes')->where(['channel_id' => $channel->id, 'type' => 'STRIKE', 'status' => 'ACTIVE'])->countAllResults();
            if ((int)$channel->strikes_count !== $sCount) $cUpdates['strikes_count'] = $sCount;

            if (!empty($cUpdates)) {
                $db->table('channels')->where('id', $channel->id)->update($cUpdates);
            }
        }

        // --- 4. SYNC USER COUNTS (Followers, Content) ---
        CLI::write("Syncing User Identity Counters...", 'cyan');
        $users = $db->table('users')->get()->getResult();
        foreach ($users as $u) {
            $uUpdates = [];

            $followers = $db->table('follows')->where('following_id', $u->id)->countAllResults();
            $following = $db->table('follows')->where('follower_id', $u->id)->countAllResults();
            
            $posts  = $db->table('posts')->where(['user_id' => $u->id, 'status' => 'published'])->countAllResults();
            $reels  = $db->table('reels')->where(['user_id' => $u->id, 'status' => 'published'])->countAllResults();
            $videos = $db->table('videos')->where(['user_id' => $u->id, 'status' => 'published'])->countAllResults();

            if ((int)$u->followers_count !== $followers) $uUpdates['followers_count'] = $followers;
            if ((int)$u->following_count !== $following) $uUpdates['following_count'] = $following;
            if ((int)$u->posts_count !== $posts) $uUpdates['posts_count'] = $posts;
            if ((int)$u->reels_count !== $reels) $uUpdates['reels_count'] = $reels;
            if ((int)$u->videos_count !== $videos) $uUpdates['videos_count'] = $videos;

            if (!empty($uUpdates)) {
                $db->table('users')->where('id', $u->id)->update($uUpdates);
            }
        }

        CLI::write("✅ Global Sync Completed! Your Database is now 100% Consistent.", 'green');
    }
}
