<?php namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ChannelModel;
use CodeIgniter\API\ResponseTrait;

class AuthController extends BaseController {
    use ResponseTrait;

    protected $userModel;
    protected $channelModel;
    protected $db;

    public function __construct() {
        $this->userModel = new UserModel();
        $this->channelModel = new ChannelModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * ✅ UPDATE FCM TOKEN SEPARATELY
     */
    public function updateFcmToken() {
        $currentUserId = $this->request->getHeaderLine('User-ID') ?: $this->request->getHeaderLine('user-id');

        if (!$currentUserId || $currentUserId === 'null' || $currentUserId === 'undefined') {
            return $this->respond([
                'status' => 401,
                'error' => 'UNAUTHORIZED',
                'message' => 'User ID missing or invalid.'
            ], 401);
        }

        $input = $this->getRequestInput();
        $fcmToken = $input['fcm_token'] ?? null;

        if (empty($fcmToken)) {
            return $this->respond([
                'status' => 400,
                'error' => 'TOKEN_MISSING',
                'message' => 'FCM Token is required'
            ], 400);
        }

        $user = $this->userModel->find($currentUserId);
        if (!$user) {
            return $this->failNotFound('User not found');
        }

        if ($user['fcm_token'] === $fcmToken) {
            return $this->respond([
                'status' => 200,
                'success' => true,
                'message' => 'Token already up to date'
            ]);
        }

        try {
            $this->userModel->update($currentUserId, ['fcm_token' => $fcmToken]);
            
            $streamToken = $this->getStreamToken((string)$currentUserId);
            $apiKey = "hd2hh25znvez";
            $apiUrl = "https://chat.stream-io-api.com/devices?api_key=" . $apiKey;

            $deviceData = [
                'user_id' => (string)$currentUserId,
                'id'      => $fcmToken,
                'push_provider' => 'firebase',
                'push_provider_name' => 'firebase' 
            ];

            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($deviceData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $streamToken,
                'stream-auth-type: jwt'
            ]);
            
            curl_exec($ch);
            curl_close($ch);

            return $this->respond([
                'status' => 200,
                'success' => true,
                'message' => 'FCM Token updated successfully'
            ]);
            
        } catch (\Exception $e) {
             return $this->respond([
                'status' => 200, 
                'success' => true, 
                'message' => 'FCM Token update processed'
            ]);
        }
    }

    /**
     * 🔥 FIXED: getStreamToken (Chat Only + Clock Buffer)
     */
    private function getStreamToken(string $userId) {
        try {
            $apiKey = "hd2hh25znvez";
            $apiSecret = "55863qe5x7p5zzam7qa4guqctcug2ny7rnzhk6kg4cdqr2uqcw35mwaxye22wnb8";

            $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
            $payload = json_encode([
                'user_id' => (string)$userId,
                'iat' => time() - 60,          // ✅ Server Time drift fix
                'exp' => time() + (3600 * 24)  // ✅ 24h Expiry
            ]);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
            $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $apiSecret, true);
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
            return preg_replace('/\s+/', '', trim($token)); 
        } catch (\Exception $e) {
            return null;
        }
    }

    public function checkUsername() {
        $input = $this->getRequestInput();
        $username = trim($input['username'] ?? '');

        if (empty($username)) {
            return $this->respond(['status' => 400, 'error' => 'USERNAME_EMPTY', 'isAvailable' => false], 400);
        }

        $user = $this->userModel->withDeleted()->where('username', $username)->first();
        return $this->respond([
            'status' => 200,
            'isAvailable' => $user ? false : true,
            'message' => $user ? 'Username already taken' : 'Username is available'
        ]);
    }

    private function getSystemSettings() {
        $builder = $this->db->table('system_settings');
        $query = $builder->get()->getResultArray();
        $settings = [];
        foreach ($query as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    private function sendEmail($to, $subject, $message) {
        $sys = $this->getSystemSettings();
        $email = \Config\Services::email();
        $config = [
            'protocol' => 'smtp',
            'SMTPHost' => $sys['smtp_host'] ?? '',
            'SMTPPort' => (int)($sys['smtp_port'] ?? 465),
            'SMTPUser' => $sys['smtp_user'] ?? '',
            'SMTPPass' => $sys['smtp_pass'] ?? '',
            'SMTPCrypto' => $sys['smtp_crypto'] ?? 'ssl',
            'mailType' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n",
            'CRLF' => "\r\n",
            'timeout' => 60,
        ];
        $email->initialize($config);
        $fromEmail = $sys['smtp_user'] ?? 'no-reply@domain.com';
        $appName = $sys['app_name'] ?? 'My Social App';
        $email->setFrom($fromEmail, $appName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($message);
        return $email->send();
    }

    private function generateAndSaveToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $insertData = [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $this->request->getIPAddress(),
            'device_info' => substr((string)$this->request->getUserAgent(), 0, 255)
        ];
        $this->db->table('auth_tokens')->insert($insertData);
        return $token;
    }

    private function getFullProfileData(int $userId) {
        $user = $this->userModel->getProfile($userId);
        if (!$user) return null;
        $channel = $this->channelModel->where('user_id', $userId)->first();
        
        $user['is_creator'] = (int)($user['is_creator'] ?? 0);
        $user['channel_id'] = $channel ? (int)$channel['id'] : null;
        $user['unique_id'] = $user['unique_id'] ?? null; 
        $user['channel_unique_id'] = $channel ? $channel['unique_id'] : null; 

        if ($channel) {
            $user['is_monetized'] = (int)($channel['is_monetization_enabled'] ?? 0);
            $user['monetization_status'] = $channel['monetization_status'] ?? 'NOT_APPLIED';
        } else {
            $user['is_monetized'] = 0;
            $user['monetization_status'] = 'NOT_APPLIED';
        }
        return $this->userModel->castUserTypes($user);
    }

    private function getRequestInput() {
        try {
            $json = $this->request->getJSON(true);
            return !empty($json) ? $json : $this->request->getVar();
        } catch (\Exception $e) {
            return $this->request->getVar();
        }
    }

    public function login() {
        $input = $this->getRequestInput();
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $fcmToken = $input['fcm_token'] ?? null;

        if (empty($email) || empty($password)) {
            return $this->respond(['status' => 400, 'error' => 'FIELDS_REQUIRED', 'message' => 'Please fill in all fields.'], 400);
        }

        $user = $this->userModel->withDeleted()->where('email', $email)->first();
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->respond(['status' => 401, 'error' => 'INVALID_CREDENTIALS', 'message' => 'Incorrect email or password.'], 401);
        }

        if (($user['is_banned'] ?? 0) == 1) {
            return $this->respond(['status' => 403, 'error' => 'ACCOUNT_BANNED', 'message' => 'Your account has been suspended.'], 403);
        }

        if ($user['is_deleted'] == 1) {
            return $this->respond(['status' => 403, 'error' => 'ACCOUNT_DELETED_RECOVERY_REQUIRED', 'user_id' => $user['id'], 'message' => 'Account scheduled for deletion.'], 403);
        }

        if (isset($user['email_verified']) && $user['email_verified'] == 0) {
            return $this->respond(['status' => 401, 'error' => 'ACCOUNT_UNVERIFIED', 'message' => 'Please verify your email.'], 401);
        }

        $updateData = ['last_active' => date('Y-m-d H:i:s')];
        if ($fcmToken) {
            $updateData['fcm_token'] = $fcmToken;
        }
        $this->userModel->update($user['id'], $updateData);

        $token = $this->generateAndSaveToken((int)$user['id']);
        $streamToken = $this->getStreamToken((string)$user['id']);

        return $this->respond([
            'status' => 200,
            'token' => $token,
            'stream_token' => $streamToken,
            'user' => $this->getFullProfileData((int)$user['id']),
            'message' => 'Login successful'
        ]);
    }

    public function recoverAccount() {
        $input = $this->getRequestInput();
        $userId = $input['user_id'] ?? null;
        if (!$userId) return $this->respond(['status' => 400, 'message' => 'User ID missing'], 400);

        $user = $this->userModel->withDeleted()->find($userId);
        if (!$user) return $this->failNotFound('User not found');

        if ($this->userModel->update($userId, ['is_deleted' => 0, 'deleted_at' => null])) {
            return $this->respond(['status' => 200, 'message' => 'Account recovered successfully.']);
        }
        return $this->respond(['status' => 500, 'message' => 'Recovery failed'], 500);
    }

    /**
     * ✅ REGISTER WITH SMART ID SYSTEM
     */
    public function register() {
        $input = $this->getRequestInput();
        $email = $input['email'] ?? '';
        $username = $input['username'] ?? '';

        $existingUser = $this->userModel->withDeleted()
            ->groupStart()
                ->where('email', $email)
                ->orWhere('username', $username)
            ->groupEnd()
            ->first();

        if ($existingUser) {
            if ($existingUser['is_deleted'] == 1) {
                return $this->respond(['status' => 403, 'error' => 'RECOVERY_REQUIRED', 'user_id' => $existingUser['id'], 'message' => 'Account deleted. Please recover.'], 403);
            }
            return $this->respond(['status' => 400, 'message' => 'Email or Username taken.'], 400);
        }

        if (!$this->validate(['username' => 'required', 'email' => 'required|valid_email', 'password' => 'required|min_length[8]'])) {
            return $this->respond(['status' => 400, 'messages' => $this->validator->getErrors()], 400);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $data = [
            'name' => $input['name'] ?? 'User',
            'username' => $username,
            'email' => $email,
            'password' => password_hash($input['password'], PASSWORD_DEFAULT),
            'verification_code' => $otp,
            'verification_code_expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'email_verified' => 0, 
            'is_verified' => 0, 
            'is_deleted' => 0, 
            'is_banned' => 0, 
            'is_creator' => 1
        ];

        $this->db->transStart();
        if ($this->userModel->insert($data)) {
            $newUserId = $this->db->insertID(); 

            $smartUserId = $this->id_generator->generate('user', $newUserId);
            $smartChannelId = $this->id_generator->generate('channel', $newUserId);

            $this->userModel->update($newUserId, ['unique_id' => $smartUserId]);

            $this->db->table('channels')->insert([
                'user_id' => $newUserId,
                'unique_id' => $smartChannelId, 
                'handle' => $username, 
                'name' => $data['name'],
                'description' => "Welcome to my channel!", 
                'category' => 'General', 
                'creator_level' => 'Trusted Creator', 
                'trust_score' => 100,               
                'is_monetization_enabled' => 0, 
                'monetization_status' => 'NOT_APPLIED',
                'created_at' => date('Y-m-d H:i:s'), 
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->transComplete();
            if ($this->db->transStatus() === false) return $this->respond(['status' => 500, 'message' => 'Transaction failed'], 500);

            $this->sendEmail($email, 'Verify Account', "Your OTP: <b>{$otp}</b>");
            return $this->respondCreated(['status' => 201, 'message' => 'OTP sent']);
        }
        return $this->respond(['status' => 500, 'message' => 'Registration failed'], 500);
    }

    public function verifyOtp() {
        $input = $this->getRequestInput();
        $user = $this->userModel->where('email', $input['email'] ?? '')->first();
        if (!$user || $user['verification_code'] !== ($input['otp'] ?? '')) {
            return $this->respond(['status' => 400, 'message' => 'Invalid OTP'], 400);
        }
        $this->userModel->update($user['id'], ['verification_code' => null, 'email_verified' => 1]);
        return $this->respond([
            'status' => 200,
            'token' => $this->generateAndSaveToken((int)$user['id']),
            'stream_token' => $this->getStreamToken((string)$user['id']),
            'user' => $this->getFullProfileData((int)$user['id']),
            'message' => 'Verified'
        ]);
    }

    public function forgotPassword() {
        $input = $this->getRequestInput();
        $user = $this->userModel->where('email', $input['email'] ?? '')->first();
        if (!$user) return $this->respond(['status' => 404, 'message' => 'Not found'], 404);
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->userModel->update($user['id'], ['reset_token' => $otp, 'reset_token_expiry' => date('Y-m-d H:i:s', strtotime('+30 minutes'))]);
        $this->sendEmail($input['email'], 'Reset OTP', "Code: <b>{$otp}</b>");
        return $this->respond(['status' => 200, 'message' => 'OTP sent']);
    }

    public function resetPassword() {
        $input = $this->getRequestInput();
        $user = $this->userModel->where(['email' => $input['email'], 'reset_token' => $input['otp']])->first();
        if (!$user) return $this->respond(['status' => 400, 'message' => 'Invalid code'], 400);
        $this->userModel->update($user['id'], ['password' => password_hash($input['password'], PASSWORD_DEFAULT), 'reset_token' => null]);
        return $this->respond(['status' => 200, 'message' => 'Reset successful']);
    }

    public function me() {
        $userId = $this->request->getHeaderLine('User-ID');
        if (!$userId) return $this->respond(['status' => 401, 'message' => 'Expired'], 401);
        return $this->respond([
            'status' => 200, 
            'user' => $this->getFullProfileData((int)$userId), 
            'stream_token' => $this->getStreamToken((string)$userId)
        ]);
    }

    public function logout() {
        $userId = $this->request->getHeaderLine('User-ID');
        if ($userId) {
            $this->userModel->update($userId, ['fcm_token' => null]);
        }
        return $this->respond(['status' => 200, 'message' => 'Logged out']);
    }
}
