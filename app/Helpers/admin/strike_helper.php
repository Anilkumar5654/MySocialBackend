<?php

use Config\Database;

/**
 * 1. SETTINGS FETCH HELPER
 */
if (!function_exists('get_strike_setting_value')) {
    function get_strike_setting_value($key, $default = 0) {
        $db = Database::connect();
        $row = $db->table('system_settings')
                  ->select('setting_value')
                  ->where('setting_key', $key)
                  ->get()->getRow();
        return $row ? (float)$row->setting_value : $default;
    }
}

/**
 * 2. EXECUTE LOGIC (ULTIMATE DEBUG VERSION)
 */
if (!function_exists('execute_strike_logic')) {
    function execute_strike_logic($data, $existingRequestId = null) {
        $db = Database::connect();
        
        $logFile = WRITEPATH . 'logs/copyright_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $debug = "\n--- 🚀 NEW STRIKE ATTEMPT [$timestamp] ---\n";
        
        $type = strtoupper($data['type']); 
        $contentType = strtoupper($data['content_type'] ?? 'VIDEO'); 
        $table = ($contentType === 'REEL') ? 'reels' : 'videos';

        // 🔍 STEP 1: Content Exists?
        $targetContent = $db->table($table)->where('id', $data['content_id'])->get()->getRow();
        if (!$targetContent) {
            $debug .= "❌ ERROR: ID {$data['content_id']} NOT FOUND in '$table'.\n";
            file_put_contents($logFile, $debug, FILE_APPEND);
            return ['status' => false, 'error' => 'Content missing'];
        }

        // 🔍 STEP 2: Channel Exists?
        $targetChannel = $db->table('channels')->where('id', $data['channel_id'])->get()->getRow();
        if (!$targetChannel) {
            $debug .= "❌ ERROR: Channel ID {$data['channel_id']} NOT FOUND in channels.\n";
            file_put_contents($logFile, $debug, FILE_APPEND);
            return ['status' => false, 'error' => 'Channel missing'];
        }

        $db->transStart();

        // Points Calculation
        $penaltyKey = 'trust_penalty_' . strtolower($contentType) . '_strike';
        $points = ($type === 'STRIKE') ? (int)get_strike_setting_value($penaltyKey, 10) : 0;
        $expiryDays = (int)get_strike_setting_value('trust_strike_expiry_days', 90);
        $expiresAt = ($type === 'STRIKE') ? date('Y-m-d H:i:s', strtotime("+$expiryDays days")) : null;

        // --- ACTION 1: Update channel_strikes ---
        $strikeUpdate = [
            'type'            => $type,
            'status'          => 'ACTIVE',
            'severity_points' => $points,
            'expires_at'      => $expiresAt,
            'locked_by'       => null,
            'locked_at'       => null
        ];
        $db->table('channel_strikes')->where('id', $existingRequestId)->update($strikeUpdate);

        if ($type === 'STRIKE') {
            // --- ACTION 2: Update Media Table ---
            $db->table($table)->where('id', $data['content_id'])->update([
                'monetization_enabled' => 0, 
                'copyright_status'     => 'STRIKED', 
                'visibility'           => 'blocked'
            ]);

            // --- ACTION 3: Update Channel Stats (Fixed Binding) ---
            $db->query("UPDATE channels SET trust_score = GREATEST(0, CAST(trust_score AS SIGNED) - ?), strikes_count = strikes_count + 1 WHERE id = ?", [(int)$points, $data['channel_id']]);

            // --- ACTION 4: Trust Log (Only if points > 0) ---
            if ($points > 0) {
                $db->table('trust_score_logs')->insert([
                    'user_id'     => $targetChannel->user_id,
                    'channel_id'  => $data['channel_id'],
                    'points'      => -(int)$points,
                    'action_type' => 'PENALTY',
                    'reason'      => 'Strike: ' . ($data['reason'] ?? 'Copyright Match')
                    // 🔥 FIX: Yahan se 'created_at' hata diya gaya hai taaki MySQL error na de!
                ]);
            }
        } 
        elseif ($type === 'CLAIM') {
            $db->table($table)->where('id', $data['content_id'])->update([
                'copyright_status'    => 'CLAIMED', 
                'original_content_id' => $data['original_content_id'] ?? null
            ]);
        }

        $db->transComplete();
        $status = $db->transStatus();

        if ($status === FALSE) {
            $error = $db->error();
            $debug .= "❌ TRANSACTION FAILED! " . $error['message'] . "\n";
        } else {
            $debug .= "🚀 SUCCESS: All updates committed.\n";
        }

        file_put_contents($logFile, $debug, FILE_APPEND);
        return ['status' => $status, 'points' => $points];
    }
}

/**
 * 3. REVERT LOGIC
 */
if (!function_exists('revert_strike_logic')) {
    function revert_strike_logic($strikeId, $newStatus = 'EXPIRED', $restoreMedia = false) {
        $db = Database::connect();
        $strike = $db->table('channel_strikes')->where('id', $strikeId)->get()->getRow();
        if (!$strike) return ['status' => false];

        $db->transStart();

        if ($strike->severity_points > 0 && $strike->type === 'STRIKE') {
            $db->query("UPDATE channels SET trust_score = LEAST(100, trust_score + ?), strikes_count = GREATEST(0, strikes_count - 1) WHERE id = ?", [$strike->severity_points, $strike->channel_id]);
        }

        if ($restoreMedia && !empty($strike->content_id)) {
            $table = ($strike->content_type === 'REEL') ? 'reels' : 'videos';
            $db->table($table)->where('id', $strike->content_id)->update([
                'visibility'           => 'public', 
                'copyright_status'     => 'NONE',
                'monetization_enabled' => 1
            ]);
        }

        $db->table('channel_strikes')->where('id', $strikeId)->update(['status' => $newStatus]);
        $db->transComplete();
        return ['status' => $db->transStatus()];
    }
}
