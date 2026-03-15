<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ExpireStrikes extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'strikes:expire';
    protected $description = 'Manages Strike Expiry & Auto Account Health Rewards using System Settings.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        helper(['admin/strike']);

        CLI::write("--- DYNAMIC ACCOUNT HEALTH ENGINE STARTING ---", 'yellow');

        // 🟢 STEP 1: LOAD SYSTEM SETTINGS (Hardcoding Khatam!)
        $settings = [];
        $query = $db->table('system_settings')->get()->getResultArray();
        foreach ($query as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $now = date('Y-m-d H:i:s');
        $fifteenDaysAgo = date('Y-m-d H:i:s', strtotime('-15 days'));

        // ==========================================
        // 1. EXPIRE OLD STRIKES
        // ==========================================
        $expiredStrikes = $db->table('channel_strikes')
                            ->where('status', 'ACTIVE')
                            ->where('type', 'STRIKE')
                            ->where('expires_at <=', $now)
                            ->get()->getResult();

        foreach ($expiredStrikes as $strike) {
            revert_strike_logic($strike->id, 'EXPIRED', false);
            CLI::write("✔ STRIKE EXPIRED: Reputation restored for Strike #{$strike->id}", 'green');
        }

        // ==========================================
        // 2. ACCOUNT HEALTH REWARDS (Using Settings)
        // ==========================================
        $channels = $db->table('channels')->where('trust_score <', 100)->get()->getResult();

        foreach ($channels as $channel) {
            // --- CLEAN STREAK LOGIC ---
            $hasRecentStrike = $db->table('channel_strikes')
                                 ->where('channel_id', $channel->id)
                                 ->where('created_at >', $fifteenDaysAgo)
                                 ->countAllResults();

            if ($hasRecentStrike == 0) {
                $alreadyLog = $db->table('trust_score_logs')
                                ->where(['channel_id' => $channel->id, 'reason' => 'Account Health: 15-Day Clean Streak'])
                                ->where('created_at >', $fifteenDaysAgo)
                                ->countAllResults();

                if ($alreadyLog == 0) {
                    // 🔥 SETTINGS SE POINTS UTHAO (Default 1 if not found)
                    $points = (int)($settings['points_clean_streak_15d'] ?? 1);
                    
                    $db->query("UPDATE channels SET trust_score = LEAST(trust_score + $points, 100) WHERE id = {$channel->id}");
                    $db->table('trust_score_logs')->insert([
                        'user_id'     => $channel->user_id,
                        'channel_id'  => $channel->id,
                        'points'      => $points,
                        'action_type' => 'REWARD',
                        'reason'      => 'Account Health: 15-Day Clean Streak',
                        'created_at'  => $now
                    ]);
                    CLI::write("✨ REWARD: +{$points} Points to {$channel->handle} (Streak)", 'cyan');
                }
            }

            // --- GENUINE ENGAGEMENT LOGIC ---
            $replies = $db->table('comments')
                          ->where('user_id', $channel->user_id)
                          ->where('parent_id IS NOT NULL')
                          ->where('created_at >', date('Y-m-d H:i:s', strtotime('-7 days')))
                          ->countAllResults();

            if ($replies >= 3) {
                $alreadyEngaged = $db->table('trust_score_logs')
                                    ->where(['channel_id' => $channel->id, 'reason' => 'Account Health: Genuine Engagement'])
                                    ->where('created_at >', date('Y-m-d H:i:s', strtotime('-7 days')))
                                    ->countAllResults();

                if ($alreadyEngaged == 0) {
                    // 🔥 SETTINGS SE POINTS UTHAO
                    $points = (int)($settings['points_genuine_engagement'] ?? 1);
                    
                    $db->query("UPDATE channels SET trust_score = LEAST(trust_score + $points, 100) WHERE id = {$channel->id}");
                    $db->table('trust_score_logs')->insert([
                        'user_id'     => $channel->user_id,
                        'channel_id'  => $channel->id,
                        'points'      => $points,
                        'action_type' => 'REWARD',
                        'reason'      => 'Account Health: Genuine Engagement',
                        'created_at'  => $now
                    ]);
                    CLI::write("💬 ENGAGEMENT: +{$points} Points to {$channel->handle}", 'blue');
                }
            }
        }

        CLI::write("--- DYNAMIC ENGINE COMPLETED ---", 'yellow');
    }
}
