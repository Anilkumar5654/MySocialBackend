<?php 

use Config\Database;

if (!function_exists('handle_system_copyright_strike')) {
    /**
     * ✅ SYSTEM COPYRIGHT ENGINE (Filtered Logs & Corrected Action)
     */
    function handle_system_copyright_strike($videoId, $videoType, $channelId, $videoHash = null, $actionType = 'STRIKE')
    {
        $db = Database::connect();
        $tableName = (strtolower($videoType) === 'reel') ? 'reels' : 'videos';
        $actionType = strtoupper($actionType); 

        try {
            // 1. Original Creator Lookup
            $originalContentId = null;
            $originalCreatorId = null;
            if ($videoHash) {
                $blacklistEntry = $db->table('copyright_blacklist')
                                    ->select('original_video_id')
                                    ->where('banned_hash', $videoHash)
                                    ->get()->getRow();
                
                if ($blacklistEntry && $blacklistEntry->original_video_id) {
                    $originalContentId = $blacklistEntry->original_video_id;
                    $origVideo = $db->table($tableName)->select('user_id')->where('id', $originalContentId)->get()->getRow();
                    $originalCreatorId = $origVideo->user_id ?? null;
                }
            }

            // 2. Penalty Calculation
            $penalty = 0;
            if ($actionType === 'STRIKE' || $actionType === 'AUTO_STRIKE') {
                $settingKey = (strtolower($videoType) === 'reel') ? 'trust_penalty_reel_strike' : 'trust_penalty_video_strike';
                $setting = $db->table('system_settings')->where('setting_key', $settingKey)->get()->getRow();
                $penalty = $setting ? (int)$setting->setting_value : 10;
            }

            $dbType = ($actionType === 'AUTO_STRIKE' || $actionType === 'STRIKE') ? 'STRIKE' : 'CLAIM';

            $db->transStart(); 

            // 3. Record in channel_strikes
            $db->table('channel_strikes')->insert([
                'channel_id'          => $channelId,
                'report_source'       => 'SYSTEM',
                'content_type'        => strtoupper($videoType),
                'content_id'          => $videoId,
                'original_content_id' => $originalContentId,
                'reason'              => ($dbType === 'STRIKE') ? 'Copyright Strike (Auto)' : 'Revenue Claim (Auto)',
                'type'                => $dbType,
                'severity_points'     => $penalty,
                'status'              => 'ACTIVE',
                'created_at'          => date('Y-m-d H:i:s')
            ]);

            // 4. Action Branching
            if ($dbType === 'STRIKE') {
                // 🔴 STRIKE LOGIC
                $db->table($tableName)->where('id', $videoId)->update([
                    'copyright_status' => 'STRIKED',
                    'visibility'       => 'blocked',
                    'monetization_enabled' => 0
                ]);

                $db->table('channels')->where('id', $channelId)
                   ->set('trust_score', "GREATEST(trust_score - $penalty, 0)", FALSE)
                   ->set('strikes_count', 'strikes_count + 1', FALSE)
                   ->update();
            } else {
                // 🔵 CLAIM LOGIC
                $db->table($tableName)->where('id', $videoId)->update([
                    'copyright_status'    => 'CLAIMED',
                    'original_content_id' => $originalContentId
                ]);

                $uploader = $db->table('channels')->select('user_id')->where('id', $channelId)->get()->getRow();
                if ($originalCreatorId && $uploader) {
                    $db->table('revenue_shares')->insert([
                        'claimed_content_id' => $videoId,
                        'content_type'       => strtoupper($videoType),
                        'original_creator_id'=> $originalCreatorId,
                        'uploader_id'        => $uploader->user_id,
                        'status'             => 'ACTIVE',
                        'created_at'         => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // --- 🟢 5. FILTERED HISTORY LOG ENTRY ---
            // Ab Claim hone par koi entry nahi hogi, sirf Strike penalty par log banega
            if ($dbType === 'STRIKE' && $penalty > 0) {
                $uploaderUser = $db->table('channels')->select('user_id')->where('id', $channelId)->get()->getRow();
                if ($uploaderUser) {
                    $db->table('trust_score_logs')->insert([
                        'user_id'     => $uploaderUser->user_id,
                        'channel_id'  => $channelId,
                        'points'      => -$penalty,
                        'action_type' => 'PENALTY',
                        'reason'      => 'System Strike: Content Match #' . $videoId,
                        'created_at'  => date('Y-m-d H:i:s')
                    ]);
                }
            }

            $db->transComplete();
            return true;

        } catch (\Exception $e) {
            log_message('error', 'Copyright Engine Error: ' . $e->getMessage());
            return false;
        }
    }
}
