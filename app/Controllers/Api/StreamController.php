<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class StreamController extends BaseController
{
    use ResponseTrait;

    /**
     * 🔥 UPGRADED JWT GENERATOR: Bina kisi logic ko touch kiye.
     * Fixed: Added video and call permissions for stable socket connection.
     * Fix added: Strict whitespace removal to prevent compact serialization errors.
     */
    private function generateManualToken($uid) {
        $apiKey = 'hd2hh25znvez'; 
        $apiSecret = '55863qe5x7p5zzam7qa4guqctcug2ny7rnzhk6kg4cdqr2uqcw35mwaxye22wnb8'; 

        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'user_id' => (string)$uid,
            'iat' => time(),
            // Video calls ke liye exp claim dalna professional rehta hai
            'exp' => time() + (3600 * 24), // 24 Hours validity
            'video' => true,               // 🔥 FIX: Required for Video Socket
            'call'  => '*'                 // 🔥 FIX: Required for Room Access
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $apiSecret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // 🔥 FINAL FIX: String concatenate karke kisi bhi tarah ka whitespace ya newline remove karna
        // Isse jwt.io ka serialization error aur 1006 connection abort fix ho jayega
        $token = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return preg_replace('/\s+/', '', trim($token)); 
    }

    /**
     * ✅ EXISTING LOGIC: Get Token for Chat/Video
     */
    public function getToken()
    {
        $uid = $this->request->getHeaderLine('User-ID');
        if (!$uid) {
            return $this->failUnauthorized('User-ID header missing hai');
        }

        $token = $this->generateManualToken($uid);

        return $this->respond([
            'success' => true,
            'token'   => $token,
            'apiKey'  => 'hd2hh25znvez'
        ]);
    }

    /**
     * 🚀 NEW LOGIC: Create Call Room & Join Link
     * Bina SDK ke calling start karne ka professional tarika
     */
    public function createCall()
    {
        $uid = $this->request->getHeaderLine('User-ID');
        if (!$uid) {
            return $this->failUnauthorized('User-ID header missing hai');
        }

        // 1. Get Call Type (default, audio_room, livestream)
        $call_type = $this->request->getVar('call_type') ?: 'default';
        
        // 2. Generate unique snake_case call ID
        $call_id = 'call_' . $uid . '_' . bin2hex(random_bytes(4));

        // 3. Generate Token
        $token = $this->generateManualToken($uid);
        $api_key = 'hd2hh25znvez';

        // 4. Create Web-Join Link (Bina SDK install kiye calls chalane ke liye)
        // Ye link aap WebView mein load karenge ya Chat message mein bhejenge
        $join_url = "https://getstream.io/video/demos/call/{$call_type}/{$call_id}?token={$token}&apiKey={$api_key}";

        return $this->respond([
            'status' => 200,
            'success' => true,
            'data' => [
                'call_id'   => $call_id,
                'call_type' => $call_type,
                'token'     => $token,
                'api_key'   => $api_key,
                'join_url'  => $join_url
            ],
            'message' => 'Call room created successfully'
        ]);
    }
}

