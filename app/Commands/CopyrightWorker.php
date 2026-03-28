<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CopyrightWorker extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'copyright:scan';
    protected $description = 'ULTIMATE Copyright Matching Engine - Scans Videos & Reels';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        CLI::write("🚀 [Copyright Engine] Starting Scan...", 'cyan');

        // 🔥 1. Scan Videos
        $this->processScan($db, 'videos');

        // 🔥 2. Scan Reels
        $this->processScan($db, 'reels');

        CLI::write("✅ [Copyright Engine] Scan Completed.", 'green');
    }

    private function processScan($db, $table)
    {
        CLI::write(">>> Checking $table...", 'yellow');

        // Unmatched content uthao (Max 100 per run taaki server hang na ho)
        $newItems = $db->table($table)
                        ->where('original_content_id', NULL)
                        ->where('frame_hashes !=', NULL)
                        ->limit(100)
                        ->get()->getResultArray();

        $matchCount = 0;

        foreach ($newItems as $target) {
            $targetHashes = explode(',', $target['frame_hashes'] ?? '');
            if (count($targetHashes) < 5) continue; // Safety check

            // Target ko baaki sabse compare karo
            $originals = $db->table($table)
                            ->where('id !=', $target['id'])
                            ->where('frame_hashes !=', NULL)
                            ->get()->getResultArray();

            foreach ($originals as $original) {
                $originalHashes = explode(',', $original['frame_hashes'] ?? '');
                
                // --- 7/10 HASH MATCHING LOGIC ---
                $common = array_intersect($targetHashes, $originalHashes);
                
                if (count($common) >= 7) { 
                    $db->table($table)->where('id', $target['id'])->update([
                        'original_content_id' => $original['id']
                    ]);

                    // 🔥 CRITICAL: Agar uploader ne AUTO_STRIKE/CLAIM set kiya hai toh action le lo
                    $this->triggerAutoAction($db, $target, $original, $table);

                    $matchCount++;
                    CLI::write("   ✨ Match Found: ID {$target['id']} linked to Original #{$original['id']}", 'green');
                    break; 
                }
            }
        }
        CLI::write("--- Finished $table. Matches injected: $matchCount", 'cyan');
    }

    private function triggerAutoAction($db, $target, $original, $table)
    {
        // Check if original creator wants auto-action
        $type = (strtolower($table) === 'reels') ? 'REEL' : 'VIDEO';
        
        // Agar handle_system_copyright_strike function exist karta hai
        if (function_exists('handle_system_copyright_strike')) {
            $action = strtoupper($original['auto_match_action'] ?? 'NONE');
            if ($action !== 'NONE') {
                handle_system_copyright_strike($target['id'], $type, $target['channel_id'], $target['video_hash'] ?? null, $action);
            }
        }
    }
}
