<?php

namespace App\Helpers;

/**
 * StreamHelper v1.0.2 - Production Ready Token Generator
 * Strictly for Chat (No Video/Audio permissions to keep payload small)
 */
class StreamHelper
{
    private static $apiKey = "hd2hh25znvez";
    private static $apiSecret = "55863qe5x7p5zzam7qa4guqctcug2ny7rnzhk6kg4cdqr2uqcw35mwaxye22wnb8";

    /**
     * 🔐 Generate Stable JWT for GetStream Chat
     */
    public static function generateToken(string $userId)
    {
        try {
            // Header: Standard HS256
            $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
            
            // Payload: Minimal for Chat Only
            $payload = json_encode([
                'user_id' => (string)$userId,
                'iat'     => time() - 120,          // ✅ 2 min clock drift buffer
                'exp'     => time() + (3600 * 24 * 7) // ✅ 7 Days Expiry (Production standard)
            ]);

            // Base64Url Safe Encoding Function
            $base64UrlEncode = function($data) {
                return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
            };

            $headerPart = $base64UrlEncode($header);
            $payloadPart = $base64UrlEncode($payload);

            // Signature Generation
            $signature = hash_hmac('sha256', "$headerPart.$payloadPart", self::$apiSecret, true);
            $signaturePart = $base64UrlEncode($signature);

            // Final Token Assembly
            $token = "$headerPart.$payloadPart.$signaturePart";
            
            // 🔥 CRITICAL: Remove any hidden whitespace or newlines
            return preg_replace('/\s+/', '', trim($token));

        } catch (\Exception $e) {
            log_message('error', 'Stream Token Generation Failed: ' . $e->getMessage());
            return null;
        }
    }

    public static function getApiKey()
    {
        return self::$apiKey;
    }
}
