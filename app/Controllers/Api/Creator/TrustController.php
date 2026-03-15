<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use Throwable;

class TrustController extends BaseController
{
    use ResponseTrait;
    protected $db;
    protected $settings = [];

    public function __construct() {
        $this->db = \Config\Database::connect();
        helper(['media', 'text', 'date', 'number']);
        
        $query = $this->db->table('system_settings')->get()->getResultArray();
        foreach ($query as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    /**
     * 📜 1. TRUST DASHBOARD DATA (Full Sync with Frontend)
     */
    public function strikeDetails()
    {
        try {
            $userId = $this->request->getHeaderLine('User-ID') ?: $this->request->getHeaderLine('user-id');
            if (!$userId) return $this->failUnauthorized('User ID missing.');

            // 1. Fetch Channel & User
            $channel = $this->db->table('channels')
                            ->select('channels.*, users.name as creator_name')
                            ->join('users', 'users.id = channels.user_id')
                            ->where('channels.user_id', $userId)
                            ->get()->getRow();

            if (!$channel) return $this->failNotFound('Channel not found.');

            // 2. Strikes Formatting
            $strikes = $this->db->table('channel_strikes')
                            ->where('channel_id', $channel->id)
                            ->orderBy('created_at', 'DESC')
                            ->get()->getResultArray();

            $expiryDays = (int)($this->settings['trust_strike_expiry_days'] ?? 45);
            $now = time();

            $formattedStrikes = array_map(function($s) use ($expiryDays, $now) {
                $expTS = $s['expires_at'] ? strtotime($s['expires_at']) : strtotime($s['created_at'] . " + $expiryDays days");
                return [
                    'id'            => (int)$s['id'],
                    'type'          => $s['type'], 
                    'reason'        => $s['reason'],
                    'status'        => $s['status'],
                    'appealStatus'  => $s['appeal_status'], 
                    'date'          => date('d M, Y', strtotime($s['created_at'])),
                    'expiry'        => date('d M, Y', $expTS),
                    'daysLeft'      => $s['status'] === 'ACTIVE' ? max(0, ceil(($expTS - $now) / 86400)) : 0,
                    'severity'      => (int)$s['severity_points'],
                    'canAppeal'     => ($s['status'] === 'ACTIVE' && $s['appeal_status'] === 'NONE')
                ];
            }, $strikes);

            // 3. Quiz Status
            $hasPassedQuiz = $this->db->table('creator_quiz_attempts')
                                ->where(['user_id' => $userId, 'is_passed' => 1])
                                ->countAllResults();

            // 🛡️ COOLDOWN CALCULATOR (New Sync Logic)
            $checkEligibility = function($reason, $days) use ($userId) {
                $log = $this->db->table('trust_score_logs')
                            ->where(['user_id' => $userId, 'reason' => $reason])
                            ->orderBy('created_at', 'DESC')->get()->getRow();
                
                if (!$log) return ['eligible' => true, 'days_left' => 0];
                
                $nextTime = strtotime($log->created_at . " + $days days");
                $isEligible = time() >= $nextTime;
                $daysLeft = $isEligible ? 0 : ceil(($nextTime - time()) / 86400);
                
                return ['eligible' => $isEligible, 'days_left' => $daysLeft];
            };

            $streak = $checkEligibility('Account Health: 15-Day Clean Streak', 15);
            $engagement = $checkEligibility('Account Health: Genuine Engagement', 7);
            $feedback = $checkEligibility('Account Health: Positive Audience Feedback', 15);

            // 🟢 MAIN RESPONSE
            return $this->respond([
                'status' => 'success', 
                'user' => [
                    'trustScore' => (int)$channel->trust_score,
                    'statusLabel' => $this->getStatusLabel($channel->trust_score),
                    'monetization_status'       => $channel->monetization_status, 
                    'monetization_applied_date' => $channel->monetization_applied_date,
                    'monetization_apply_count'  => (int)$channel->monetization_apply_count,
                    'monetization_reason'       => $channel->monetization_reason 
                ],
                'summary' => [
                    'total_strikes'  => (int)$channel->strikes_count,
                    'total_warnings' => (int)($channel->warnings_count ?? 0),
                    'active_violations' => count(array_filter($formattedStrikes, fn($v) => $v['status'] === 'ACTIVE'))
                ],
                'strikes' => $formattedStrikes,
                'history' => $this->db->table('trust_score_logs')
                                    ->where('user_id', $userId)
                                    ->orderBy('created_at', 'DESC')->limit(15)->get()->getResultArray(), 
                'config' => [
                    'min_to_apply'  => (int)($this->settings['trust_min_to_apply'] ?? 80),
                    'min_to_keep'   => (int)($this->settings['trust_min_to_keep'] ?? 50),
                    'quiz_points'   => (int)($this->settings['points_recovery_quiz'] ?? 5),
                    'recovery' => [
                        'streak_15d' => (int)($this->settings['points_clean_streak_15d'] ?? 15),
                        'feedback'   => (int)($this->settings['points_positive_feedback_ratio'] ?? 8),
                        'engagement' => (int)($this->settings['points_genuine_engagement'] ?? 4)
                    ]
                ],
                'penalty_guide' => [
                    'video_strike'      => (int)($this->settings['trust_penalty_video_strike'] ?? 4),
                    'channel_strike'    => (int)($this->settings['trust_penalty_channel_strike'] ?? 15),
                    'copyright_strike'  => (int)($this->settings['trust_penalty_copyright_strike'] ?? 6),
                    'guideline_strike'  => (int)($this->settings['trust_penalty_community_guideline_strike'] ?? 7),
                    'fake_report'       => (int)($this->settings['trust_penalty_fake_report'] ?? 2)
                ],
                'tasks_status' => [
                    'quiz_completed' => (bool)$hasPassedQuiz,
                    'streak_eligible' => (bool)$streak['eligible'],
                    'streak_days_left' => (int)$streak['days_left'],
                    'engagement_eligible' => (bool)$engagement['eligible'],
                    'engagement_days_left' => (int)$engagement['days_left'],
                    'feedback_eligible' => (bool)$feedback['eligible'],
                    'feedback_days_left' => (int)$feedback['days_left']
                ]
            ]);

        } catch (Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * 📝 2. SUBMIT STRIKE APPEAL
     */
    public function submitStrikeAppeal($strikeId = null)
    {
        try {
            $userId = $this->request->getHeaderLine('User-ID') ?: $this->request->getHeaderLine('user-id');
            $json = $this->request->getJSON();
            $appealReason = $json->reason ?? $this->request->getPost('reason');

            if (!$userId || !$strikeId) return $this->fail('Invalid Request.');
            if (!$appealReason || strlen(trim($appealReason)) < 20) {
                return $this->fail('Appeal reason must be at least 20 characters.');
            }

            $channel = $this->db->table('channels')->select('id')->where('user_id', $userId)->get()->getRow();
            
            $strike = $this->db->table('channel_strikes')
                            ->where(['id' => $strikeId, 'channel_id' => $channel->id])
                            ->get()->getRow();

            if (!$strike || $strike->status !== 'ACTIVE' || $strike->appeal_status !== 'NONE') {
                return $this->fail('Strike cannot be appealed.');
            }

            $this->db->table('channel_strikes')->where('id', $strikeId)->update([
                'appeal_status' => 'PENDING',
                'appeal_reason' => trim($appealReason),
                'appealed_at'   => date('Y-m-d H:i:s')
            ]);

            return $this->respond(['status' => 'success', 'message' => 'Appeal submitted successfully.']);
        } catch (Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    /**
     * 💰 3. APPLY FOR MONETIZATION
     */
    public function applyForMonetization()
    {
        try {
            $userId = $this->request->getHeaderLine('User-ID') ?: $this->request->getHeaderLine('user-id');
            if (!$userId) return $this->failUnauthorized('User ID missing.');

            $minApplyScore = (int)($this->settings['trust_min_to_apply'] ?? 80);
            
            $channel = $this->db->table('channels')->where('user_id', $userId)->get()->getRow();

            if ((int)$channel->monetization_apply_count >= 3 && $channel->monetization_status === 'REJECTED') {
                return $this->fail('Maximum application attempts (3) reached. You are no longer eligible.');
            }

            $allowedStatuses = ['NONE', 'REJECTED', 'NOT_APPLIED'];
            if (!in_array($channel->monetization_status, $allowedStatuses)) {
                return $this->fail('Application already in progress or approved.');
            }

            if ($channel->trust_score < $minApplyScore) {
                return $this->fail("Minimum Trust Score required is $minApplyScore%.");
            }

            $this->db->table('channels')->where('id', $channel->id)->update([
                'monetization_status'       => 'PENDING',
                'monetization_apply_count'  => (int)$channel->monetization_apply_count + 1,
                'monetization_applied_date' => date('Y-m-d H:i:s'),
                'monetization_reason'       => null 
            ]);

            return $this->respond(['status' => 'success', 'message' => "Application submitted successfully!"]);
        } catch (Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    /**
     * ✅ 4. QUIZ SUBMISSION
     */
    public function submitQuiz() {
        try {
            $userId = $this->request->getHeaderLine('User-ID') ?: $this->request->getHeaderLine('user-id');
            $json = $this->request->getJSON();
            if (!$userId) return $this->failUnauthorized();
            
            $alreadyPassed = $this->db->table('creator_quiz_attempts')->where(['user_id' => $userId, 'is_passed' => 1])->getRow();
            if ($alreadyPassed) return $this->fail('Quiz already completed.');

            $isPassed = ($json->score >= ceil($json->total * 0.7)) ? 1 : 0;
            $points = (int)($this->settings['points_recovery_quiz'] ?? 5);

            $this->db->table('creator_quiz_attempts')->insert([
                'user_id' => $userId,
                'score_obtained' => $json->score,
                'is_passed' => $isPassed,
                'attempted_at' => date('Y-m-d H:i:s')
            ]);

            if ($isPassed) {
                $this->db->query("UPDATE channels SET trust_score = LEAST(trust_score + $points, 100) WHERE user_id = $userId");
                
                $channel = $this->db->table('channels')->where('user_id', $userId)->get()->getRow();
                $this->db->table('trust_score_logs')->insert([
                    'user_id' => $userId,
                    'channel_id' => $channel->id,
                    'points' => $points,
                    'action_type' => 'REWARD',
                    'reason' => 'Passed Creator Safety Quiz',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            return $this->respond(['status' => 'success', 'isPassed' => (bool)$isPassed]);
        } catch (Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    private function getStatusLabel($score) {
        if ($score >= 90) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 50) return 'Warning';
        return 'Critical';
    }
}
