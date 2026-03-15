<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SystemSync extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'system:sync';
    protected $description = 'Comprehensive Sync: Fixes all counters for Users, Channels, and Hashtags (Excludes Subscriptions).';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        CLI::write("🚀 Starting Global System Sync...", 'yellow');

        // --- 1. SYNC HASHTAG COUNTS ---
        // 'taggables' table se counts fetch karke 'hashtags' table sync karein
        CLI::write("Updating Hashtag usage counts...", 'cyan');
        $db->query("
            UPDATE hashtags h 
            SET h.posts_count = (
                SELECT COUNT(*) FROM taggables t 
                WHERE t.hashtag_id = h.id AND t.is_visible = 1
            )
        ");

        // --- 2. SYNC CHANNEL METRICS ---
        CLI::write("Syncing Channel Videos & Strike counts...", 'cyan');
        $channels = $db->table('channels')->get()->getResult();
        foreach ($channels as $channel) {
            $cUpdates = [];
            
            // Sync Videos Count (Only Published)
            $vCount = $db->table('videos')->where(['channel_id' => $channel->id, 'status' => 'published'])->countAllResults();
            if ((int)$channel->videos_count !== $vCount) $cUpdates['videos_count'] = $vCount;

            // Sync Strike Count (Only ACTIVE STRIKES)
            $sCount = $db->table('channel_strikes')->where(['channel_id' => $channel->id, 'type' => 'STRIKE', 'status' => 'ACTIVE'])->countAllResults();
            if ((int)$channel->strikes_count !== $sCount) $cUpdates['strikes_count'] = $sCount;

            // 🛡️ Auto-Unblock Eligibility Check (AuthContext Sync)
            if ($channel->strikes_count >= 3) {
                $lastStrike = $db->table('channel_strikes')
                                 ->where(['channel_id' => $channel->id, 'type' => 'STRIKE', 'status' => 'ACTIVE'])
                                 ->orderBy('created_at', 'DESC')->get()->getRow();
                
                if ($lastStrike) {
                    $days = ($sCount >= 10) ? 90 : (($sCount >= 5) ? 15 : 5);
                    if (time() >= strtotime($lastStrike->created_at . " + $days days")) {
                        CLI::write("   -> Channel ID {$channel->id}: Penalty period expired.", 'green');
                    }
                }
            }

            if (!empty($cUpdates)) {
                $db->table('channels')->where('id', $channel->id)->update($cUpdates);
            }
        }

        // --- 3. SYNC USER COUNTS ---
        CLI::write("Syncing User Identity Counters (Followers, Posts, Reels, Videos)...", 'cyan');
        $users = $db->table('users')->get()->getResult();
        foreach ($users as $u) {
            $uUpdates = [];

            // Followers & Following (From 'follows' table)
            $followers = $db->table('follows')->where('following_id', $u->id)->countAllResults();
            $following = $db->table('follows')->where('follower_id', $u->id)->countAllResults();
            
            // Content Counts (From respective tables)
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

        CLI::write("✅ Global Sync Completed Successfully!", 'green');
    }
}

