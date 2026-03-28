<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;

class AuthFilter implements FilterInterface
{
    /**
     * Final Master Auth Filter
     * 1. ✅ FIX: Allows OPTIONS requests (Preflight) to pass without token.
     * 2. Handles 'Bearer' extraction robustly.
     * 3. Checks expiry using database time (NOW()).
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // 🛑 MASTER FIX FOR CORS/PREFLIGHT
        // Agar request 'OPTIONS' hai (App check kar raha hai ki server zinda hai ya nahi),
        // toh use bina token ke jaane do.
        if (strtolower($request->getMethod()) === 'options') {
            return;
        }

        // 1. Get Authorization Header (Case-Insensitive)
        $authHeader = $request->getHeaderLine('Authorization') ?: $request->getHeaderLine('authorization');

        $token = null;

        // 2. Extract Token using Regex (Handles multiple spaces correctly)
        if (!empty($authHeader) && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } 
        
        // 3. Fallback: Check Query Params
        if (!$token) {
            $token = $request->getVar('token');
        }

        // 4. Validate existence
        if (empty($token)) {
            return Services::response()
                ->setJSON([
                    'success' => false,
                    'message' => 'Authorization token missing'
                ])
                ->setStatusCode(401);
        }

        try {
            // 5. Database Verification
            $userId = $this->validateToken((string)$token);

            if (!$userId) {
                return Services::response()
                    ->setJSON([
                        'success' => false,
                        'message' => 'Invalid or expired token. Please login again.'
                    ])
                    ->setStatusCode(401);
            }

            // 6. Inject User-ID into request headers for the Controller
            $request->setHeader('User-ID', (string)$userId);

        } catch (Exception $e) {
            return Services::response()
                ->setJSON([
                    'success' => false,
                    'message' => 'Authentication system error',
                    'error'   => $e->getMessage()
                ])
                ->setStatusCode(500);
        }
    }

    /**
     * Validate Token against Database
     * ✨ UPGRADE: Added is_active check to support Session Killing
     */
    private function validateToken(string $token)
    {
        $db = \Config\Database::connect();
        
        // Trim to ensure exact string match
        $cleanToken = trim($token);

        $row = $db->table('auth_tokens')
                  ->select('user_id')
                  ->where('token', $cleanToken)
                  ->where('is_active', 1) // 👈 ✨ NEW: Only allow active sessions
                  // ✅ MASTER FIX: Use MySQL's NOW() to prevent Timezone issues
                  ->where('expires_at > NOW()') 
                  ->get()
                  ->getRow();

        return $row ? $row->user_id : null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
