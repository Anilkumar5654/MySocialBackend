<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use App\Models\PostModel;
use App\Models\ChannelModel;

class UserController extends BaseController {
    use ResponseTrait;
    protected $userModel;
    protected $postModel;
    protected $channelModel;
    protected $db;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->postModel = new PostModel();
        $this->channelModel = new ChannelModel();
        $this->db = \Config\Database::connect();
        // 🔥 Helper loaded for location hierarchy
        helper(['media', 'url', 'date', 'filesystem', 'text', 'location']);
    }

    /**
     * 🔥 1. NORMALIZER
     */
    private function normalizeKeys(array $item): array {
        $boolKeys = ['is_following', 'is_followed_by_viewer', 'is_monetization_enabled', 'is_verified', 'is_liked'];
        foreach ($boolKeys as $key) {
            if (array_key_exists($key, $item)) {
                $item[$key] = (bool)$item[$key];
            }
        }
        foreach (array_keys($item) as $key) {
            if (preg_match('/[A-Z]/', $key)) {
                unset($item[$key]);
            }
        }
        foreach ($item as $key => $value) {
            if (is_array($value)) {
                $item[$key] = $this->normalizeKeys($value);
            }
        }
        return $item;
    }

    /**
     * ✅ 2. FETCH PROFILE
     */
    public function fetchProfile() {
        $targetUserId = $this->request->getGet('user_id') ?? $this->request->getVar('user_id');
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$targetUserId) return $this->fail('User ID missing', 400);
        
        $profile = $this->userModel->getProfile((int)$targetUserId, (int)$currentUserId);
        if (!$profile) return $this->failNotFound('User not found');
        
        $channel = $this->db->table('channels')
            ->select('id, name, strikes_count, can_report, is_monetization_enabled, monetization_status')
            ->where('user_id', $targetUserId)
            ->get()
            ->getRowArray();

        $profile['channel_id'] = $channel ? $channel['id'] : null;
        $profile['channel_name'] = $channel ? $channel['name'] : null;
        $profile['is_monetization_enabled'] = $channel ? (int)$channel['is_monetization_enabled'] : 0;
        $profile['monetization_enabled'] = $profile['is_monetization_enabled'];
        $profile['monetization_status'] = $channel ? $channel['monetization_status'] : 'NOT_APPLIED';
        $profile['display_name'] = $profile['channel_name'] ?: ($profile['name'] ?: $profile['username']);
        $profile['status'] = $this->calculateStatus($profile['last_active']);
        $profile['can_message'] = $this->checkMutualInteraction($currentUserId, $targetUserId);
        
        $pCount = (int)($profile['posts_count'] ?? 0);
        $vCount = (int)($profile['videos_count'] ?? 0);
        $rCount = (int)($profile['reels_count'] ?? 0);
        $profile['total_content'] = $pCount + $vCount + $rCount;

        $user_permissions = ['upload_blocked' => false, 'claim_blocked' => false, 'block_message' => ""];
        if ($channel) {
            $user_permissions['claim_blocked'] = ((int)$channel['can_report'] === 0);
            $strikes = (int)$channel['strikes_count'];
            $lastStrike = $this->db->table('channel_strikes')->select('MAX(created_at) as last_date')->where(['channel_id' => $channel['id'], 'type' => 'STRIKE'])->get()->getRowArray();
            $blockDays = ($strikes >= 10) ? 90 : (($strikes >= 5) ? 15 : (($strikes >= 3) ? 5 : 0));
            if ($blockDays > 0 && !empty($lastStrike['last_date'])) {
                $expiryTime = strtotime($lastStrike['last_date'] . " + $blockDays days");
                if (time() < $expiryTime) {
                    $remainingDays = ceil(($expiryTime - time()) / 86400);
                    $user_permissions['upload_blocked'] = true;
                    $user_permissions['block_message'] = "Upload blocked for $remainingDays days.";
                }
            }
        }
        $profile['channel_permissions'] = $user_permissions;
        return $this->respond(['success' => true, 'user' => $this->normalizeKeys($profile)]);
    }

    /**
     * ✅ 2.5 SEARCH LOCATIONS
     */
    public function searchLocations() {
        $type = $this->request->getGet('type');      
        $query = $this->request->getGet('query') ?? ''; 
        $context = $this->request->getGet('context') ?? ''; 

        if (!$type) return $this->respond([]);

        $results = search_static_locations($type, $query, $context);
        
        return $this->respond($results);
    }

    /**
     * ✅ 3. GET CHAT LIST
     */
    public function getChatList() {
        $currentUserId = (int)$this->request->getHeaderLine('User-ID');
        if (!$currentUserId) return $this->failUnauthorized('User ID missing');
        $this->db->table('messages')->where(['recipient_id' => $currentUserId, 'is_delivered' => 0])->update(['is_delivered' => 1]);
        $sql = "SELECT c.id as conversation_id, c.last_message_at, m.content as last_message, m.sender_id as last_sender_id, m.is_read, m.is_delivered, m.is_deleted_everyone, u.id as partner_id, u.username as partner_username, u.name as partner_name, u.avatar as partner_avatar, u.is_verified as partner_verified, u.last_active as partner_last_active, (SELECT COUNT(id) FROM messages WHERE conversation_id = c.id AND recipient_id = ? AND is_read = 0) as unread_count FROM conversations c JOIN users u ON (u.id = IF(c.user1_id = ?, c.user2_id, c.user1_id)) LEFT JOIN messages m ON c.last_message_id = m.id WHERE (c.user1_id = ? OR c.user2_id = ?) ORDER BY COALESCE(c.last_message_at, '2000-01-01') DESC";
        $conversations = $this->db->query($sql, [$currentUserId, $currentUserId, $currentUserId, $currentUserId])->getResultArray();
        foreach ($conversations as &$chat) {
            $chat['partner_status'] = $this->calculateStatus($chat['partner_last_active']);
            $chat['can_message'] = $this->checkMutualInteraction($currentUserId, $chat['partner_id']);
            if (isset($chat['is_deleted_everyone']) && $chat['is_deleted_everyone'] == 1) {
                $chat['last_message'] = "🚫 Message deleted";
            } elseif ($chat['last_message']) {
                $chat['last_message'] = ($chat['last_sender_id'] == $currentUserId) ? "You: " . $chat['last_message'] : $chat['last_message'];
            } else {
                $chat['last_message'] = "Start a secure conversation";
            }
            $chat = $this->normalizeKeys($chat);
        }
        return $this->respond(['success' => true, 'conversations' => $conversations]);
    }

    /**
     * ✅ 4. TOGGLE FOLLOW (🔥 100% FIXED MUTUAL & FOLLOW BACK LOGIC)
     */
    public function toggleFollow() {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        
        // 🔥 FIX 1: Robust Input Capture (Handles both JSON and Form-Data from React Native)
        $json = $this->request->getJSON(true) ?: [];
        $targetUserId = $json['user_id'] ?? $this->request->getPost('user_id') ?? $this->request->getVar('user_id');
        
        if (!$currentUserId || !$targetUserId || $currentUserId == $targetUserId) {
            return $this->fail('Invalid Request', 400);
        }
        
        $followData = ['follower_id' => $currentUserId, 'following_id' => $targetUserId];
        
        $this->db->transStart();
        $existing = $this->db->table('follows')->where($followData)->get()->getRow();
        $isFollowing = false;
        
        if ($existing) {
            // Unfollow Logic
            $this->db->table('follows')->where($followData)->delete();
            $this->db->table('users')->where('id', $currentUserId)->decrement('following_count');
            $this->db->table('users')->where('id', $targetUserId)->decrement('followers_count');
        } else {
            // Follow Logic
            $this->db->table('follows')->insert($followData);
            $this->db->table('users')->where('id', $currentUserId)->increment('following_count');
            $this->db->table('users')->where('id', $targetUserId)->increment('followers_count');
            $isFollowing = true;
        }
        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return $this->failServerError('Transaction failed.');
        }

        // 🔥 FIX 2: Check Mutual Status (Is the target person following the current user?)
        $isFollowedByViewer = $this->db->table('follows')
            ->where(['follower_id' => $targetUserId, 'following_id' => $currentUserId])
            ->countAllResults() > 0;
            
        // 🔥 FIX 3: Calculate 'can_message' (Do both follow each other?)
        $canMessage = ($isFollowing && $isFollowedByViewer);

        // Return COMPLETE STATE to the frontend so UI updates instantly
        return $this->respond([
            'success'               => true, 
            'is_following'          => $isFollowing,          // React Native mostly uses this
            'following'             => $isFollowing,          // Backward compatibility
            'is_followed_by_viewer' => $isFollowedByViewer,   // "Follow Back" UI check
            'can_message'           => $canMessage            // Enable message button instantly
        ]);
    }

    /**
     * ✅ 5. GET FOLLOWERS LIST
     */
    public function getFollowers() {
        $targetUserId = $this->request->getGet('user_id');
        $currentUserId = (int)$this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 20; $offset = ($page - 1) * $limit;
        
        $builder = $this->db->table('follows f');
        $builder->select('u.id, u.username, u.name, u.avatar, u.is_verified, c.name as channel_name');
        $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = $currentUserId AND following_id = u.id) as is_following");
        $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = u.id AND following_id = $currentUserId) as is_followed_by_viewer");
        $builder->join('users u', 'f.follower_id = u.id');
        $builder->join('channels c', 'c.user_id = u.id', 'left');
        $builder->where(['f.following_id' => $targetUserId, 'u.is_deleted' => 0]);
        
        $users = $builder->get($limit, $offset)->getResultArray();
        
        foreach ($users as &$u) {
            $u['display_name'] = $u['channel_name'] ?: ($u['name'] ?: $u['username']);
            $u['is_following'] = (int)$u['is_following'] > 0;
            $u['is_followed_by_viewer'] = (int)$u['is_followed_by_viewer'] > 0;
            $u = $this->normalizeKeys($u);
        }
        return $this->respond(['success' => true, 'users' => $users, 'hasMore' => count($users) === $limit]);
    }

    /**
     * ✅ 6. GET FOLLOWING LIST
     */
    public function getFollowing() {
        $targetUserId = $this->request->getGet('user_id');
        $currentUserId = (int)$this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 20; $offset = ($page - 1) * $limit;
        
        $builder = $this->db->table('follows f');
        $builder->select('u.id, u.username, u.name, u.avatar, u.is_verified, c.name as channel_name');
        $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = $currentUserId AND following_id = u.id) as is_following");
        $builder->select("(SELECT COUNT(*) FROM follows WHERE follower_id = u.id AND following_id = $currentUserId) as is_followed_by_viewer");
        $builder->join('users u', 'f.following_id = u.id');
        $builder->join('channels c', 'c.user_id = u.id', 'left');
        $builder->where(['f.follower_id' => $targetUserId, 'u.is_deleted' => 0]);
        
        $users = $builder->get($limit, $offset)->getResultArray();
        
        foreach ($users as &$u) {
            $u['display_name'] = $u['channel_name'] ?: ($u['name'] ?: $u['username']);
            $u['is_following'] = (int)$u['is_following'] > 0;
            $u['is_followed_by_viewer'] = (int)$u['is_followed_by_viewer'] > 0;
            $u = $this->normalizeKeys($u);
        }
        return $this->respond(['success' => true, 'users' => $users, 'hasMore' => count($users) === $limit]);
    }

    /**
     * ✅ 7. PUBLIC: GET USER POSTS
     */
    public function getUserPosts() {
        $targetUserId = (int)$this->request->getGet('user_id');
        $currentUserId = (int)$this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 12; $offset = ($page - 1) * $limit;
        $totalCount = $this->db->table('posts')->where(['user_id' => $targetUserId, 'status' => 'published'])->countAllResults();
        $isFollower = false;
        if ($currentUserId && $currentUserId !== $targetUserId) {
            $isFollower = $this->db->table('follows')->where(['follower_id' => $currentUserId, 'following_id' => $targetUserId])->countAllResults() > 0;
        }
        $builder = $this->db->table('posts p')->select('p.*, u.username, u.name, u.avatar, c.name as channel_name') ->join('users u', 'u.id = p.user_id')->join('channels c', 'c.user_id = u.id', 'left')->where(['p.user_id' => $targetUserId, 'p.status' => 'published']);
        if ($isFollower || $currentUserId === $targetUserId) $builder->whereIn('p.feed_scope', ['public', 'followers']); else $builder->where('p.feed_scope', 'public');
        $posts = $builder->orderBy('p.created_at', 'DESC')->get($limit, $offset)->getResultArray();
        return $this->respond(['success' => true, 'posts' => $this->formatPostMedia($posts), 'hasMore' => ($offset + count($posts)) < $totalCount, 'currentPage' => $page]);
    }

    /**
     * ✅ 8. ME: GET MY POSTS
     */
    public function getMyPosts() {
        $userId = $this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 12; $offset = ($page - 1) * $limit;
        $totalCount = $this->db->table('posts')->where('user_id', $userId)->countAllResults();
        $posts = $this->db->table('posts p')->select('p.*, u.username, u.name, u.avatar, c.name as channel_name') ->join('users u', 'u.id = p.user_id')->join('channels c', 'c.user_id = u.id', 'left')->where('p.user_id', $userId) ->orderBy('p.created_at', 'DESC')->get($limit, $offset)->getResultArray();
        return $this->respond(['success' => true, 'posts' => $this->formatPostMedia($posts), 'hasMore' => ($offset + count($posts)) < $totalCount, 'currentPage' => $page]);
    }

    /**
     * ✅ 9. PUBLIC: GET USER VIDEOS
     */
    public function getUserVideos() {
        $targetUserId = $this->request->getGet('user_id');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 12; $offset = ($page - 1) * $limit;
        $totalCount = $this->db->table('videos')->where(['user_id' => $targetUserId, 'status' => 'published', 'visibility' => 'public'])->countAllResults();
        $videos = $this->db->table('videos')->where(['user_id' => $targetUserId, 'status' => 'published', 'visibility' => 'public']) ->orderBy('created_at', 'DESC')->get($limit, $offset)->getResultArray();
        foreach ($videos as &$v) $v = $this->normalizeKeys($v);
        return $this->respond(['success' => true, 'items' => $videos, 'hasMore' => ($offset + count($videos)) < $totalCount, 'currentPage' => $page]);
    }

    /**
     * ✅ 10. ME: GET MY VIDEOS
     */
    public function getMyVideos() {
        $userId = $this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 12; $offset = ($page - 1) * $limit;
        $totalCount = $this->db->table('videos')->where('user_id', $userId)->countAllResults();
        $videos = $this->db->table('videos')->where('user_id', $userId)->orderBy('created_at', 'DESC')->get($limit, $offset)->getResultArray();
        foreach ($videos as &$v) $v = $this->normalizeKeys($v);
        return $this->respond(['success' => true, 'items' => $videos, 'hasMore' => ($offset + count($videos)) < $totalCount, 'currentPage' => $page]);
    }

    /**
     * ✅ 11. ME: GET MY REELS
     */
    public function getMyReels() {
        $userId = $this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 12; $offset = ($page - 1) * $limit;
        $totalCount = $this->db->table('reels')->where('user_id', $userId)->countAllResults();
        $reels = $this->db->table('reels')->where('user_id', $userId)->orderBy('created_at', 'DESC')->get($limit, $offset)->getResultArray();
        foreach ($reels as &$r) $r = $this->normalizeKeys($r);
        return $this->respond(['success' => true, 'reels' => $reels, 'hasMore' => ($offset + count($reels)) < $totalCount, 'currentPage' => $page]);
    }

    /**
     * ✅ 12. EDIT PROFILE
     */
    public function editProfile() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        
        $currentUser = $this->userModel->find($userId);
        $data = $this->request->getPost();
        
        $updateData = [];

        $fields = [
            'name', 'username', 'bio', 'location', 'phone', 
            'dob', 'gender', 'district', 'state', 'country'
        ];

        foreach ($fields as $f) {
            if (isset($data[$f])) {
                $val = trim($data[$f]);
                $updateData[$f] = ($val === '') ? null : $val;
            }
        }

        $avatarFile = $this->request->getFile('profile');
        if ($avatarFile && $avatarFile->isValid() && !$avatarFile->hasMoved()) {
            $avatarPath = upload_media_master($avatarFile, 'profile');
            if ($avatarPath) {
                $updateData['avatar'] = $avatarPath;
                if (!empty($currentUser['avatar'])) @unlink(FCPATH . 'uploads/' . $currentUser['avatar']);
            }
        }

        $coverFile = $this->request->getFile('cover');
        if ($coverFile && $coverFile->isValid() && !$coverFile->hasMoved()) {
            $coverPath = upload_media_master($coverFile, 'cover');
            if ($coverPath) {
                $updateData['cover_photo'] = $coverPath;
                if (!empty($currentUser['cover_photo'])) @unlink(FCPATH . 'uploads/' . $currentUser['cover_photo']);
            }
        }

        if (!empty($updateData)) {
            $this->userModel->update($userId, $updateData);
            if (isset($updateData['name'])) {
                $this->db->table('channels')->where('user_id', $userId)->update(['name' => $updateData['name']]);
            }
        }

        $updatedUser = $this->userModel->getProfile((int)$userId); 
        return $this->respond([
            'success' => true, 
            'user' => $this->normalizeKeys($updatedUser),
            'message' => 'Profile updated successfully'
        ]);
    }

    /**
     * ✅ 13. SUBMIT KYC
     */
    public function submitKYC() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();
        $frontImg = $this->request->getFile('front_image');
        $backImg = $this->request->getFile('back_image');
        if (!$frontImg || !$backImg) return $this->fail('Images required.');
        $frontPath = upload_media_master($frontImg, 'kyc');
        $backPath = upload_media_master($backImg, 'kyc');
        $kycData = ['user_id' => $userId, 'full_name' => $this->request->getPost('full_name'), 'document_type' => $this->request->getPost('document_type'), 'document_number' => $this->request->getPost('document_number'), 'front_image_url' => $frontPath, 'back_image_url' => $backPath, 'status' => 'PENDING', 'submitted_at' => date('Y-m-d H:i:s')];
        $this->db->table('user_kyc_details')->replace($kycData);
        $this->userModel->update($userId, ['kyc_status' => 'PENDING']);
        return $this->respond(['success' => true, 'message' => 'KYC Submitted']);
    }

    /**
     * ✅ 14. SEARCH
     */
    public function search() {
        $query = $this->request->getGet('q');
        if (empty($query)) return $this->respond(['results' => ['users' => []]]);
        $users = $this->db->table('users u')->select('u.id, u.username, u.name, u.avatar, u.is_verified, u.last_active, c.name as channel_name') ->join('channels c', 'c.user_id = u.id', 'left')->groupStart()->like('u.username', $query)->orLike('u.name', $query)->groupEnd() ->where('u.is_deleted', 0)->limit(20)->get()->getResultArray();
        foreach ($users as &$u) {
            $u['display_name'] = $u['name'] ?: ($u['channel_name'] ?: $u['username']);
            $u['status'] = $this->calculateStatus($u['last_active']);
            $u = $this->normalizeKeys($u);
        }
        return $this->respond(['success' => true, 'results' => ['users' => $users]]);
    }

    /**
     * ✅ 15. GET SAVED POSTS
     */
    public function getSavedPosts() {
        $userId = $this->request->getHeaderLine('User-ID');
        $page = (int)($this->request->getGet('page') ?? 1);
        $limit = 12; $offset = ($page - 1) * $limit;
        $totalCount = $this->db->table('saves')->where(['user_id' => $userId, 'saveable_type' => 'post'])->countAllResults();
        $posts = $this->db->table('saves s')->select('p.*, u.username, u.name, u.avatar')->join('posts p', 's.saveable_id = p.id')->join('users u', 'p.user_id = u.id') ->where(['s.user_id' => $userId, 's.saveable_type' => 'post', 'p.status' => 'published'])->orderBy('s.created_at', 'DESC')->get($limit, $offset)->getResultArray();
        return $this->respond(['success' => true, 'posts' => $this->formatPostMedia($posts), 'hasMore' => ($offset + count($posts)) < $totalCount, 'currentPage' => $page]);
    }

    /**
     * ✅ 16. HEARTBEAT & KYC STATUS
     */
    public function heartbeat() {
        $uid = $this->request->getHeaderLine('User-ID');
        if ($uid) $this->db->table('users')->where('id', $uid)->update(['last_active' => date('Y-m-d H:i:s')]);
        return $this->respond(['success' => true]);
    }

    public function getKYCStatus() {
        $uid = $this->request->getHeaderLine('User-ID');
        $kyc = $this->db->table('user_kyc_details')->where('user_id', $uid)->get()->getRow();
        return $this->respond(['success' => true, 'status' => $kyc ? $kyc->status : 'NOT_SUBMITTED']);
    }

    /**
     * ✅ 17. DELETE ACCOUNT
     */
    public function delete() {
        $uid = $this->request->getHeaderLine('User-ID');
        if ($uid) {
            $this->db->transStart();
            $this->userModel->update($uid, ['is_deleted' => 1, 'status' => 'DEACTIVATED']);
            $this->db->table('posts')->where('user_id', $uid)->update(['status' => 'archived']);
            $this->db->transComplete();
        }
        return $this->respond(['success' => true]);
    }

    /**
     * ✅ 18. GET CREATOR FINANCES (Earnings, Payouts, Pending)
     * Planning: Calculate totals from earnings and withdrawals tables based on status and date range.
     */
    public function getCreatorFinances() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->failUnauthorized();

        // Date Range logic (Default to last 30 days if not provided)
        $startDate = $this->request->getGet('start_date') ?: date('Y-m-d', strtotime('-30 days'));
        $endDate   = $this->request->getGet('end_date') ?: date('Y-m-d');

        // 1. Total Earnings (Sum of all approved/paid earnings in range)
        $totalEarnings = $this->db->table('creator_earnings')
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'paid'])
            ->where("created_at BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'")
            ->selectSum('amount')
            ->get()->getRow()->amount ?? 0;

        // 2. Total Payouts (Sum of completed withdrawals)
        $totalPayouts = $this->db->table('withdrawals')
            ->where(['user_id' => $userId, 'status' => 'completed'])
            ->selectSum('amount')
            ->get()->getRow()->amount ?? 0;

        // 3. Pending Balance (Sum of pending earnings)
        $pendingAmount = $this->db->table('creator_earnings')
            ->where(['user_id' => $userId, 'status' => 'pending'])
            ->selectSum('amount')
            ->get()->getRow()->amount ?? 0;

        // 4. Current Wallet Balances
        $creatorWallet = $this->db->table('creator_wallets')->where('user_id', $userId)->get()->getRow();
        $spendingWallet = $this->db->table('spending_wallets')->where('user_id', $userId)->get()->getRow();

        // 5. Last Transactions (5 records)
        $transactions = $this->db->table('wallet_transactions')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        foreach ($transactions as &$tx) {
            $tx = $this->normalizeKeys($tx);
        }

        return $this->respond([
            'success' => true,
            'stats' => [
                'total_earnings'  => (float)$totalEarnings,
                'total_payouts'   => (float)$totalPayouts,
                'pending_amount'  => (float)$pendingAmount,
                'creator_balance' => (float)($creatorWallet->balance ?? 0),
                'spending_balance'=> (float)($spendingWallet->balance ?? 0),
                'currency'        => $creatorWallet->currency ?? 'USD',
                'period'          => ['start' => $startDate, 'end' => $endDate]
            ],
            'last_transactions' => $transactions
        ]);
    }

    /**
     * 🛠️ HELPERS
     */
    private function formatPostMedia($posts) {
        foreach ($posts as &$post) {
            $post['media'] = $this->db->table('post_media')->where('post_id', $post['id'])->get()->getResultArray();
            $post = $this->normalizeKeys($post);
        }
        return $posts;
    }

    private function calculateStatus($ts) {
        if (!$ts) return "Offline";
        $lastActive = strtotime($ts);
        $diff = time() - $lastActive;
        if ($diff <= 65) return "Online";
        if ($diff < 3600) return round($diff / 60) . "m ago";
        if ($diff < 86400) return round($diff / 3600) . "h ago";
        return date("d M", $lastActive);
    }

    private function checkMutualInteraction($u1, $u2) {
        if (!$u1 || !$u2) return false;
        $f1 = $this->db->table('follows')->where(['follower_id' => $u1, 'following_id' => $u2])->countAllResults();
        $f2 = $this->db->table('follows')->where(['follower_id' => $u2, 'following_id' => $u1])->countAllResults();
        return ($f1 > 0 && $f2 > 0);
    }
}
