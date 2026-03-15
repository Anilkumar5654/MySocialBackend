<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class SyncChannelTrust extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'channel:sync_trust';
    protected $description = 'Updated Sync: 4-Level Creator System & Auto-Suspension Logic.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        // 1. System Settings se threshold uthana (Default 50 rakha hai Restricted ke liye)
        $setting = $db->table('system_settings')
                      ->where('setting_key', 'trust_min_to_keep')
                      ->get()->getRow();
        
        $minToKeep = $setting ? (int)$setting->setting_value : 50; 

        CLI::write("--- Starting Updated Channel Trust Sync ---", 'yellow');
        CLI::write("System Suspension Threshold: $minToKeep", 'cyan');

        $channels = $db->table('channels')->get()->getResult();

        foreach ($channels as $channel) {
            $currentScore = (int)$channel->trust_score;
            $updates = [];

            // --- RULE 1: Score Range Fix (0 to 100) ---
            $normalizedScore = $currentScore;
            if ($currentScore > 100) $normalizedScore = 100;
            if ($currentScore < 0) $normalizedScore = 0;

            if ($normalizedScore !== $currentScore) {
                $updates['trust_score'] = $normalizedScore;
            }

            // --- RULE 2: New Level Management (4-Tier System) ---
            if ($normalizedScore >= 90) {
                $newLevel = 'Trusted Creator'; // 🟢 90-100
            } elseif ($normalizedScore >= 70) {
                $newLevel = 'Normal Creator';  // 🟡 70-89
            } elseif ($normalizedScore >= 50) {
                $newLevel = 'Risky Creator';   // 🟠 50-69
            } else {
                $newLevel = 'Restricted Creator'; // 🔴 0-49
            }

            if ($newLevel !== $channel->creator_level) {
                $updates['creator_level'] = $newLevel;
            }

            // --- RULE 3: Monetization Auto-Suspension ---
            // Agar score threshold (50) se niche gaya toh monetization OFF
            if ($normalizedScore < $minToKeep && $channel->is_monetization_enabled == 1) {
                $updates['is_monetization_enabled'] = 0;
                $updates['monetization_status'] = 'SUSPENDED';
                CLI::write("SUSPENDED: @{$channel->handle} (Score: $normalizedScore - $newLevel)", 'red');
            }

            // --- 3. Update Execution ---
            if (!empty($updates)) {
                $updates['updated_at'] = date('Y-m-d H:i:s');
                $db->table('channels')->where('id', $channel->id)->update($updates);
            }
        }

        CLI::write("--- Sync Completed Successfully ---", 'green');
    }
}
