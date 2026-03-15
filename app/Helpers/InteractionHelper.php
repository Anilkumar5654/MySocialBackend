<?php

namespace App\Helpers;

/**
 * 👑 MASTER INTERACTION HELPER (CLEAN VERSION)
 * 1. No 'total_views' update (Safe to delete column).
 * 2. Strict Security Checks (Monetization ON/OFF).
 * 3. Qualified Views Only (15s+).
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

    private function loadEngineSettings()
    {
        $query = $this->db->table('system_settings')->get()->getResultArray();
        foreach ($query as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    public function handleInteraction($creatorId, $viewerId, $contentId, $type, $action)
    {
        // 1. Block Self-Interaction
        if ($creatorId == $viewerId && $action !== 'view') {
            return false; 
        }

        $config = $this->getInteractionConfig($action);
        if (!$config) return false;

        // 🟢 VIRAL SCORE: Always calculate
        $viralWeight = (float)($this->settings[$config['viral_key']] ?? 0);

        // 🛡️ 2. EARNINGS LOGIC
        $pointsToAdd = 0;
        $monetizedTypes = ['video', 'reel']; 
        
        // Check: 'view' action par paisa nahi, sirf 'qualified_view' par
        if (in_array($type, $monetizedTypes) && $action !== 'view') {
            
            // A. CHANNEL LEVEL CHECK
            $creatorData = $this->getCreatorFullStatus($creatorId);
            $minToKeep = (int)($this->settings['trust_min_to_keep'] ?? 85);

            if ($creatorData && 
                $creatorData->trust_score >= $minToKeep && 
                $creatorData->monetization_status === 'APPROVED' && 
                $creatorData->is_monetization_enabled == 1) { 
                
                // B. CONTENT LEVEL CHECK
                if ($this->isContentMonetizationActive($contentId, $type)) {
                    
                    // C. Get Points
                    $dynamicPointKey = $config['point_base_key'] . '_' . $type;
                    
                    if ($config['point_base_key'] !== 'none') {
                        $pointsToAdd = (float)($this->settings[$dynamicPointKey] ?? 0);
                    }

                    // D. FRAUD CHECK
                    if (in_array($action, ['like', 'comment', 'share'])) {
                        if (!$this->checkQualifiedViewExists($viewerId, $contentId, $type)) {
                            $pointsToAdd = 0; 
                        }
                    }
                }
            }
        }

        // 📈 3. UPDATE CREATOR WALLET
        if ($pointsToAdd > 0) {
            $this->updateCreatorPoints($creatorId, $action, $pointsToAdd, $type);
        }

        // 🚀 4. UPDATE VIRAL SCORE
        if ($viralWeight > 0) {
            $this->updateViralScore($contentId, $type, $viralWeight);
        }

        return true;
    }

    // --- SUPPORT FUNCTIONS ---

    private function getInteractionConfig($action)
    {
        return match($action) {
            'like'           => ['point_base_key' => 'point_like', 'viral_key' => 'viral_weight_like'],
            'share'          => ['point_base_key' => 'point_share', 'viral_key' => 'viral_weight_share'],
            'comment'        => ['point_base_key' => 'point_comment', 'viral_key' => 'viral_weight_comment'],
            'view'           => ['point_base_key' => 'none', 'viral_key' => 'viral_weight_view'], 
            'qualified_view' => ['point_base_key' => 'point_view_qualified', 'viral_key' => 'viral_weight_qualified_view'],
            default          => null
        };
    }

    private function getCreatorFullStatus($creatorId)
    {
        return $this->db->table('channels')
                        ->select('trust_score, monetization_status, is_monetization_enabled')
                        ->where('user_id', $creatorId)
                        ->get()
                        ->getRow();
    }

    private function isContentMonetizationActive($contentId, $type)
    {
        $table = ($type === 'reel') ? 'reels' : 'videos';
        $row = $this->db->table($table)->select('monetization_enabled')->where('id', $contentId)->get()->getRow();
        return ($row && $row->monetization_enabled == 1);
    }

    private function checkQualifiedViewExists($viewerId, $contentId, $type)
    {
        return $this->db->table('views')
                        ->where(['user_id' => $viewerId, 'viewable_id' => $contentId, 'viewable_type' => $type])
                        ->countAllResults() > 0;
    }

    private function updateCreatorPoints($creatorId, $action, $points, $type)
    {
        $today = date('Y-m-d');
        
        // 🔥 REMOVED 'view' -> 'total_views' mapping completely.
        $column = match($action) {
            'qualified_view' => 'qualified_views',
            'like'           => 'likes_count',
            'share'          => 'shares_count',
            'comment'        => 'comments_count',
            default          => null
        };

        // Agar column match nahi hua (jaise ki normal 'view'), to yahi ruk jao
        if (!$column) return;

        // SQL Query ab sirf inhi 4 columns par chalegi: 
        // qualified_views, likes_count, shares_count, comments_count
        $sql = "INSERT INTO creator_daily_points 
                (user_id, date, $column, quality_points, created_at) 
                VALUES (?, ?, 1, ?, NOW()) 
                ON DUPLICATE KEY UPDATE 
                $column = $column + 1, 
                quality_points = quality_points + ?";

        $this->db->query($sql, [$creatorId, $today, $points, $points]);
    }

    private function updateViralScore($contentId, $type, $weight)
    {
        $table = ($type === 'reel') ? 'reels' : 'videos';
        if ($table) {
            $sql = "UPDATE `$table` SET viral_score = viral_score + ? WHERE id = ?";
            $this->db->query($sql, [$weight, $contentId]);
        }
    }
}