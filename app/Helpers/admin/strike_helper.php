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
 * 2. EXECUTE LOGIC (Issue Strike / Claim / Warning)
 * Optimized: No logs for Claim/Warning. Strict penalty for Strikes.
 */
if (!function_exists('execute_strike_logic')) {
    function execute_strike_logic($data, $existingRequestId = null) {
        $db = Database::connect();
        $type = strtoupper($data['type']); // STRIKE, WARNING, CLAIM
        
        // Severity Points Calculation
        if ($type === 'WARNING' || $type === 'CLAIM') {
            $points = 0;
        } else {
            // Check dynamic settings for Video or Reel strikes
            $penaltyKey = 'trust_penalty_' . strtolower($data['content_type'] ?? 'video') . '_strike';
            $points = (int)get_strike_setting_value($penaltyKey, 10); 
        }
        
        $expiryDays = (int)get_strike_setting_value('trust_strike_expiry_days', 90);
        $expiresAt = ($type === 'STRIKE') ? date('Y-m-d H:i:s', strtotime("+$expiryDays days")) : null;

        $db->transStart();

        // --- 1. Strike Record Update/Insert ---
        $strikeData = [
            'type'                => $type,
            'status'              => 'ACTIVE',
            'severity_points'     => $points,
            'expires_at'          => $expiresAt,
            'original_content_id' => $data['original_content_id'] ?? null,
            'reason'              => $data['reason'] ?? 'Policy Violation',
            'description'         => $data['description'] ?? 'Admin Action'
        ];

        if ($existingRequestId) {
            $db->table('channel_strikes')->where('id', $existingRequestId)->update($strikeData);
        } else {
            $strikeData['channel_id']   = $data['channel_id'];
            $strikeData['content_type'] = strtoupper($data['content_type'] ?? 'VIDEO');
            $strikeData['content_id']   = $data['content_id'];
            $strikeData['report_source']= 'MANUAL_ADMIN';
            $strikeData['created_at']   = date('Y-m-d H:i:s');
            $db->table('channel_strikes')->insert($strikeData);
        }

        // --- 2. Channel Stats Update ---
        if ($points > 0 && $type === 'STRIKE') {
            $db->query("UPDATE channels SET trust_score = GREATEST(0, trust_score - ?), strikes_count = strikes_count + 1 WHERE id = ?", [$points, $data['channel_id']]);
        } 
        elseif ($type === 'WARNING') {
            $db->query("UPDATE channels SET warnings_count = warnings_count + 1 WHERE id = ?", [$data['channel_id']]);
        }

        // --- 3. Content Lockdown ---
        if (!empty($data['content_id'])) {
            $table = (strtoupper($data['content_type'] ?? 'VIDEO') == 'VIDEO') ? 'videos' : 'reels';
            if ($type === 'STRIKE') {
                $db->table($table)->where('id', $data['content_id'])->update([
                    'monetization_enabled' => 0, 
                    'copyright_status'     => 'STRIKED', 
                    'visibility'           => 'blocked'
                ]);
            } else {
                $db->table($table)->where('id', $data['content_id'])->update([
                    'copyright_status'    => ($type === 'CLAIM') ? 'CLAIMED' : 'NONE', 
                    'original_content_id' => ($type === 'CLAIM') ? ($data['original_content_id'] ?? null) : null
                ]);
            }
        }

        // --- 4. History Log (Only for Strike) ---
        if ($points > 0 && $type === 'STRIKE') {
            $channel = $db->table('channels')->select('user_id')->where('id', $data['channel_id'])->get()->getRow();
            if ($channel) {
                $db->table('trust_score_logs')->insert([
                    'user_id'     => $channel->user_id,
                    'channel_id'  => $data['channel_id'],
                    'points'      => -$points,
                    'action_type' => 'PENALTY',
                    'reason'      => 'Strike: ' . ($data['reason'] ?? 'Copyright'),
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
            }
        }

        $db->transComplete();
        return ['status' => $db->transStatus(), 'points' => $points];
    }
}

/**
 * 3. REVERT LOGIC (Smart Restoration)
 * $newStatus: EXPIRED (Cron) or APPEAL_APPROVED (Admin)
 * $restoreMedia: 
 * - true: Appeal approve hone par (Points + Video wapas)
 * - false: Strike purani hone par (Sirf Points wapas, Video blocked rahega)
 */
if (!function_exists('revert_strike_logic')) {
    function revert_strike_logic($strikeId, $newStatus = 'EXPIRED', $restoreMedia = false) {
        $db = Database::connect();
        $strike = $db->table('channel_strikes')->where('id', $strikeId)->get()->getRow();
        if (!$strike) return ['status' => false];

        $db->transStart();

        // --- 1. Restore Points & Counts ---
        if ($strike->severity_points > 0 && $strike->type === 'STRIKE') {
            $db->query("UPDATE channels SET trust_score = LEAST(100, trust_score + ?), strikes_count = GREATEST(0, strikes_count - 1) WHERE id = ?", [$strike->severity_points, $strike->channel_id]);
        } 
        elseif ($strike->type === 'WARNING') {
            $db->query("UPDATE channels SET warnings_count = GREATEST(0, warnings_count - 1) WHERE id = ?", [$strike->channel_id]);
        }

        // --- 2. Log History ---
        if ($strike->severity_points > 0) {
            $channel = $db->table('channels')->select('user_id')->where('id', $strike->channel_id)->get()->getRow();
            if ($channel) {
                $db->table('trust_score_logs')->insert([
                    'user_id'     => $channel->user_id,
                    'channel_id'  => $strike->channel_id,
                    'points'      => (int)$strike->severity_points,
                    'action_type' => 'REWARD',
                    'reason'      => ($newStatus === 'EXPIRED' ? 'Expired: ' : 'Revoked: ') . $strike->reason,
                    'created_at'  => date('Y-m-d H:i:s')
                ]);
            }
        }

        // --- 3. Conditional Media Restore ---
        if ($restoreMedia && !empty($strike->content_id)) {
            $table = ($strike->content_type == 'VIDEO') ? 'videos' : 'reels';
            $db->table($table)->where('id', $strike->content_id)->update([
                'visibility'           => 'public', 
                'copyright_status'     => 'NONE',
                'monetization_enabled' => 1
            ]);
        }

        // --- 4. Final Record Update ---
        $db->table('channel_strikes')->where('id', $strikeId)->update([
            'status' => $newStatus,
            'locked_by' => null,
            'locked_at' => null
        ]);

        $db->transComplete();
        return ['status' => $db->transStatus()];
    }
}
