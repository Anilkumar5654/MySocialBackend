<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Throwable;

class DashboardController extends BaseController
{
    use ResponseTrait;
    protected $db;

    public function __construct() {
        $this->db = \Config\Database::connect();
        // Helpers for formatting and media
        helper(['text', 'number', 'media', 'url', 'currency']); 
    }

    /**
     * ✅ New Tier Logic: Matches CLI and React Native
     */
    private function calculateTier($score) {
        if ($score >= 90) return "Trusted Creator";
        if ($score >= 70) return "Normal Creator";
        if ($score >= 50) return "Risky Creator";
        return "Restricted Creator";
    }

    /**
     * 🟢 OVERVIEW API
     */
    public function index() {
        try {
            // Check for User-ID in headers (Support for both cases)
            $userId = $this->request->getHeaderLine('User-ID') ?: $this->request->getHeaderLine('user-id'); 
            
            if (!$userId) {
                return $this->failUnauthorized('User ID missing.');
            }

            // 1. Fetch Channel & User Info
            $channel = $this->db->table('channels')
                            ->select('channels.*, users.kyc_status, users.followers_count, users.preferred_currency')
                            ->join('users', 'users.id = channels.user_id')
                            ->where('channels.user_id', $userId)
                            ->get()->getRow();

            if (!$channel) {
                return $this->failNotFound('Channel not found.');
            }
            
            $prefCurrency = $channel->preferred_currency ?? 'INR';

            // 2. Violation Counts
            $activeStrikes = $this->db->table('channel_strikes')
                            ->where(['channel_id' => $channel->id, 'status' => 'ACTIVE', 'type' => 'STRIKE'])
                            ->countAllResults();
            
            $activeWarnings = $this->db->table('channel_strikes')
                            ->where(['channel_id' => $channel->id, 'status' => 'ACTIVE', 'type' => 'WARNING'])
                            ->countAllResults();

            // 3. Monetization Status logic
            $isMonetized = ($channel->monetization_status === 'APPROVED' && $channel->is_monetization_enabled == 1);

            // 4. Performance & Content
            $perf = $this->getGlobalPerformance($userId);
            $recentVideos = $this->getMinimalRecent($userId);

            // ✅ FINAL RESPONSE: Synced with React Native CreatorStudioScreen
            return $this->respond([
                'status' => 'success',
                'user' => [
                    'name' => $channel->name,
                    'username' => $channel->handle,
                    'avatar' => $channel->avatar, // React Native handles formatting via getMediaUrl
                    'tier' => $this->calculateTier((int)$channel->trust_score),
                    'trustScore' => (int)$channel->trust_score,
                    'kycStatus' => $channel->kyc_status, 
                    'monetizationStatus' => $channel->monetization_status, 
                    'strikesCount' => (int)$activeStrikes,
                    'warningsCount' => (int)$activeWarnings,
                    'isMonetized' => (bool)$isMonetized,
                ],
                'channel' => [
                    'id' => (int)$channel->id,
                    'unique_id' => $channel->unique_id
                ],
                'performance' => [
                    'views' => (int)($perf['views'] ?? 0),
                    'qualifiedViews' => (int)$this->getQualifiedViews($userId),
                    'followers' => (int)($channel->followers_count ?? 0),
                    'engagement' => $perf['engagement'] ?? '0%'
                ],
                'recent_videos' => $recentVideos,
                'wallet' => [
                    'currency' => (string)$prefCurrency
                ]
            ]);

        } catch (Throwable $e) {
            return $this->failServerError('Dashboard Error: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 🛠️ INTERNAL HELPERS
    // ==========================================

    private function getMinimalRecent($userId) {
        // Union Query for Videos and Reels
        $videoQuery = $this->db->table('videos')
            ->select('id, title, thumbnail_url as thumbnail, views_count as views, status, \'video\' as type, created_at')
            ->where(['user_id' => $userId, 'status' => 'published', 'visibility' => 'public'])
            ->getCompiledSelect();

        $reelQuery = $this->db->table('reels')
            ->select('id, caption as title, thumbnail_url as thumbnail, views_count as views, status, \'reel\' as type, created_at')
            ->where(['user_id' => $userId, 'status' => 'published', 'visibility' => 'public'])
            ->getCompiledSelect();

        $results = $this->db->query("$videoQuery UNION $reelQuery ORDER BY created_at DESC LIMIT 3")->getResultArray();
        
        foreach ($results as &$vid) {
            // Keep raw path for React Native's getMediaUrl utility
            $vid['status'] = strtoupper($vid['status']);
        }
        return $results;
    }

    private function getGlobalPerformance($userId) {
        try {
            $v = $this->db->table('videos')->selectSum('views_count', 'v')->selectSum('likes_count', 'l')->selectSum('comments_count', 'c')->where('user_id', $userId)->get()->getRow();
            $r = $this->db->table('reels')->selectSum('views_count', 'v')->selectSum('likes_count', 'l')->selectSum('comments_count', 'c')->where('user_id', $userId)->get()->getRow();
            
            $totalViews = ($v->v ?? 0) + ($r->v ?? 0);
            $totalInteractions = ($v->l ?? 0) + ($r->l ?? 0) + ($v->c ?? 0) + ($r->c ?? 0);
            
            $engagement = ($totalViews > 0) ? round(($totalInteractions / $totalViews) * 100, 1) . '%' : '0%';
            
            return ['views' => $totalViews, 'engagement' => $engagement];
        } catch (Throwable $e) { 
            return ['views' => 0, 'engagement' => '0%']; 
        }
    }

    private function getQualifiedViews($userId) {
        try {
            $row = $this->db->table('creator_daily_points')->selectSum('qualified_views')->where('user_id', $userId)->get()->getRow();
            return (int)($row->qualified_views ?? 0);
        } catch (Throwable $e) { return 0; }
    }
}
