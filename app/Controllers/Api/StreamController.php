<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Helpers\StreamHelper; // 🔥 Added StreamHelper

class StreamController extends BaseController
{
    use ResponseTrait;

    /**
     * 🔥 UPDATED: generateManualToken now uses StreamHelper
     * Isse poore app mein same stable JWT signature rahega.
     */
    private function generateManualToken($uid) {
        return StreamHelper::generateToken((string)$uid);
    }

    /**
     * ✅ EXISTING LOGIC: Get Token for Chat
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
            'apiKey'  => StreamHelper::getApiKey() // ✅ API Key helper se li gayi hai
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

        // 3. Generate Token via Helper
        $token = $this->generateManualToken($uid);
        $api_key = StreamHelper::getApiKey();

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
