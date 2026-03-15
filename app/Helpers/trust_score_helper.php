<?php

/**
 * 👑 TRUST SCORE HELPER (Upgraded & Future-Proof)
 * Path: /var/www/html/app/Helpers/trust_score_helper.php
 */

if (!function_exists('adjust_trust_score')) {
    /**
     * Main Function: User ke actions ke hisaab se trust score adjust karta hai.
     */
    function adjust_trust_score($userId, $action, $id = null, $type = 'videos')
    {
        $db = \Config\Database::connect();

        // 1. Channel aur KYC status fetch karna
        $channel = $db->table('channels')
            ->select('channels.id, channels.trust_score, users.kyc_status')
            ->join('users', 'users.id = channels.user_id')
            ->where('channels.user_id', $userId)
            ->get()->getRowArray();

        if (!$channel) return false;

        // 2. Saari Settings uthana (Yahan future keys jaise 'points_per_comment' add kar sakte ho)
        $query = $db->table('system_settings')
            ->whereIn('setting_key', [
                'points_per_video_upload', 
                'max_video_activity_points', 
                'trust_initial_bonus', 
                'points_bonus_kyc'
            ])
            ->get()->getResultArray();
        $settings = array_column($query, 'setting_value', 'setting_key');

        // 3. Action decide karna
        if ($action === 'add') {
            return _apply_trust_addition($db, $userId, $channel, $id, $type, $settings);
        } elseif ($action === 'subtract') {
            return _apply_trust_subtraction($db, $userId, $channel, $id, $type, $settings);
        }

        return false;
    }
}

/**
 * INTERNAL: Points Add karne ka logic
 */
if (!function_exists('_apply_trust_addition')) {
    function _apply_trust_addition($db, $userId, $channel, $id, $type, $settings)
    {
        $initial = (int)($settings['trust_initial_bonus'] ?? 10);
        $kycPoints = ($channel['kyc_status'] === 'APPROVED') ? (int)($settings['points_bonus_kyc'] ?? 25) : 0;
        
        // Future-Proof calculation: Yahan se baaki activity points bhi minus honge agar baad mein jode gaye
        $earnedVideoPoints = (int)$channel['trust_score'] - $initial - $kycPoints;

        // Check: Kya user ne video activity ki limit (e.g. 20) reach kar li hai?
        if ($earnedVideoPoints < (int)($settings['max_video_activity_points'] ?? 20)) {
            $table = ($type === 'reel') ? 'reels' : 'videos';
            
            // Daily Limit Check: Din ki sirf pehli video par points milenge
            $alreadyToday = $db->table($table)
                ->where(['user_id' => $userId, 'visibility' => 'public'])
                ->whereIn('status', ['published', 'processing'])
                ->where('created_at >=', date('Y-m-d 00:00:00'))
                ->where('id !=', $id)
                ->countAllResults();

            if ($alreadyToday === 0) {
                $points = (int)($settings['points_per_video_upload'] ?? 1);
                $newScore = min(100, (int)$channel['trust_score'] + $points);
                
                return $db->table('channels')->where('id', $channel['id'])->update(['trust_score' => $newScore]);
            }
        }
        return false;
    }
}

/**
 * INTERNAL: Points Minus karne ka logic (The Ultimate Protection)
 */
if (!function_exists('_apply_trust_subtraction')) {
    function _apply_trust_subtraction($db, $userId, $channel, $id, $type, $settings)
    {
        // 1. BASE SCORE PROTECTION (Floor)
        // Future mein yahan login_points, comment_points etc. jidenge
        $initial     = (int)($settings['trust_initial_bonus'] ?? 10);
        $kycPoints   = ($channel['kyc_status'] === 'APPROVED') ? (int)($settings['points_bonus_kyc'] ?? 25) : 0;
        $baseScore   = $initial + $kycPoints; 
        
        $currentScore = (int)$channel['trust_score'];

        // Logic: Agar score base se zyada hai, tabhi bucket se points nikalenge
        if ($currentScore > $baseScore) {
            $table = ($type === 'reel') ? 'reels' : 'videos';
            $videoData = $db->table($table)->where('id', $id)->get()->getRow();
            
            if ($videoData) {
                $videoDate = date('Y-m-d', strtotime($videoData->created_at));
                
                // Point-Earner Check: Kya ye us din ki pehli/akeli public video thi?
                $othersThatDay = $db->table($table)
                    ->where(['user_id' => $userId, 'visibility' => 'public'])
                    ->where('id !=', $id)
                    ->where('created_at LIKE', "$videoDate%")
                    ->countAllResults();

                if ($othersThatDay === 0) {
                    $pointsPerVideo = (int)($settings['points_per_video_upload'] ?? 1);
                    
                    // max() ensure karta hai ki deletion aapke Base Score ko na chhede
                    $newScore = max($baseScore, $currentScore - $pointsPerVideo);
                    
                    return $db->table('channels')->where('id', $channel['id'])->update(['trust_score' => $newScore]);
                }
            }
        }
        return false; // Point minus nahi hua kyunki bucket khali hai ya logic match nahi hua
    }
}
