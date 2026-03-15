<?php

namespace App\Helpers;

class AdSecurityHelper {
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
    }

    // 🚀 FIXED: Ab ye advertiserId bhi lega logic check karne ke liye
    public function getRevenueLogic($videoId, $isReel, $cost, $sharePercent, $advertiserId = 0) {
        if (!$videoId || $videoId <= 0) {
            return ['eligible' => false, 'revenue' => 0, 'creator_id' => 0, 'debug' => 'Direct Ad/No Video', 'actual_is_reel' => $isReel];
        }

        $table = $isReel ? 'reels' : 'videos';
        
        $content = $this->db->table($table)
            ->select("$table.user_id as creator_id, $table.monetization_enabled as video_mon, channels.monetization_status, channels.is_monetization_enabled as channel_mon")
            ->join('channels', "channels.user_id = $table.user_id", 'left')
            ->where("$table.id", $videoId)
            ->get()->getRow();

        // [Smart Fallback Logic Same Rahega...]
        // (Jo tune likha hai wo sahi hai fallback ke liye)

        if (!$content) return ['eligible' => false, 'revenue' => 0, 'creator_id' => 0, 'debug' => 'Content Not Found', 'actual_is_reel' => $isReel];

        $isEligible = true;
        $debug = "Eligible";

        // 🔥 CRITICAL FIX: Self-Boost Protection
        // Agar Advertiser ID aur Video Owner (Creator) ID same hai, toh Revenue 0 hoga.
        if ((int)$advertiserId === (int)$content->creator_id) {
            $isEligible = false; 
            $debug = "Self-Boost: No Revenue";
        } 
        elseif (($content->monetization_status ?? '') !== 'APPROVED') {
            $isEligible = false; $debug = "Channel Not Approved";
        } elseif ($content->channel_mon != 1) {
            $isEligible = false; $debug = "Channel Mon Disabled";
        } elseif ($content->video_mon != 1) {
            $isEligible = false; $debug = "Video Mon Disabled";
        }

        $revenue = 0;
        if ($isEligible) {
            $multiplier = $isReel ? 0.50 : 1.0; 
            $revenue = round(($cost * ($sharePercent / 100)) * $multiplier, 6);
        }

        return [
            'eligible'       => $isEligible,
            'revenue'        => $revenue,
            'creator_id'     => (int)$content->creator_id,
            'debug'          => $debug,
            'actual_is_reel' => $isReel
        ];
    }

    public function isFraud($viewerId, $creatorId) {
        if ($viewerId && (int)$viewerId === (int)$creatorId) return true; 
        return false;
    }
}
