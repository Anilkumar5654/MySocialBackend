<?php

namespace App\Controllers\Api\User;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class RewardsController extends BaseController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * ✅ GET REWARD STATUS
     * Points balance aur check-in status dikhane ke liye
     */
    public function getStatus()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        
        // 1. Current Points Balance
        $points = $this->db->table('user_reward_points')
            ->where('user_id', $userId)
            ->get()->getRow();

        // 2. Check if today's reward is already claimed
        $claimedToday = $this->db->table('user_reward_logs')
            ->where(['user_id' => $userId, 'action' => 'daily_checkin', 'date' => date('Y-m-d')])
            ->countAllResults() > 0;

        return $this->respond([
            'success' => true,
            'balance' => (int)($points->points ?? 0),
            'claimed_today' => $claimedToday,
            'next_reward_in' => "Tomorrow"
        ]);
    }

    /**
     * ✅ CLAIM DAILY CHECK-IN
     */
    public function claimDaily()
    {
        $userId = $this->request->getHeaderLine('User-ID');
        $today = date('Y-m-d');

        // Check Transaction Safety
        $this->db->transStart();

        $alreadyClaimed = $this->db->table('user_reward_logs')
            ->where(['user_id' => $userId, 'action' => 'daily_checkin', 'date' => $today])
            ->countAllResults() > 0;

        if ($alreadyClaimed) {
            return $this->failResourceExists('Aaj ka reward pehle hi mil chuka hai!');
        }

        // Add 10 Points (App Setting se bhi utha sakte ho)
        $pointsToAdd = 10;

        // 1. Log the action
        $this->db->table('user_reward_logs')->insert([
            'user_id' => $userId,
            'action'  => 'daily_checkin',
            'points'  => $pointsToAdd,
            'date'    => $today,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // 2. Update user's points balance
        $exists = $this->db->table('user_reward_points')->where('user_id', $userId)->countAllResults() > 0;
        if ($exists) {
            $this->db->table('user_reward_points')->where('user_id', $userId)->increment('points', $pointsToAdd);
        } else {
            $this->db->table('user_reward_points')->insert(['user_id' => $userId, 'points' => $pointsToAdd]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return $this->failServerError('Kuch galti hui, koshish karte rahein.');
        }

        return $this->respond([
            'success' => true,
            'message' => 'Points added!',
            'claimed_points' => $pointsToAdd
        ]);
    }
}
