<?php

namespace App\Helpers;

/**
 * 🚀 VIRAL ENGINE HELPER (ADS EDITION)
 * 1. Removed all Point/Wallet/Earning logic.
 * 2. Focuses exclusively on Content Visibility & Viral Score.
 * 3. Lightweight & Fast for Production.
 */
class InteractionHelper
{
    protected $db;
    protected $settings = [];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->loadEngineSettings();
    }

    /**
     * System settings se viral weights load karta hai
     */
    private function loadEngineSettings()
    {
        $query = $this->db->table('system_settings')
                         ->where('setting_group', 'algorithm') // Sirf algo settings lo
                         ->orWhere('setting_group', 'general')
                         ->get()->getResultArray();

        foreach ($query as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    /**
     * Main Function: Sirf viral score aur algorithm parameters update karega
     */
    public function handleInteraction($creatorId, $viewerId, $contentId, $type, $action)
    {
        // Self-interaction par viral score nahi badhna chahiye (Spam Protection)
        if ($creatorId == $viewerId) {
            return false; 
        }

        $config = $this->getViralConfig($action);
        if (!$config) return false;

        // 📈 VIRAL WEIGHT: Settings se weight uthao (Default 0 agar missing ho)
        $viralWeight = (float)($this->settings[$config['viral_key']] ?? 0);

        // 🚀 UPDATE VIRAL SCORE
        // Isse video Trending aur Feed mein upar aayegi
        if ($viralWeight > 0) {
            $this->updateViralScore($contentId, $type, $viralWeight);
        }

        return true;
    }

    /**
     * Action ke hisab se viral setting key map karta hai
     */
    private function getViralConfig($action)
    {
        return match($action) {
            'like'           => ['viral_key' => 'viral_weight_like'],
            'share'          => ['viral_key' => 'viral_weight_share'],
            'comment'        => ['viral_key' => 'viral_weight_comment'],
            'view'           => ['viral_key' => 'viral_weight_view'], 
            'qualified_view' => ['viral_key' => 'viral_weight_qualified_view'],
            default          => null
        };
    }

    /**
     * Database mein Viral Score update karne ka logic
     */
    private function updateViralScore($contentId, $type, $weight)
    {
        $table = ($type === 'reel') ? 'reels' : 'videos';
        
        // Quality Check: Ensure table exists
        if ($table) {
            $sql = "UPDATE `$table` SET viral_score = viral_score + ? WHERE id = ?";
            $this->db->query($sql, [$weight, $contentId]);
        }
    }
}
