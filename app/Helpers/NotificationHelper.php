<?php

namespace App\Helpers;

use Config\Database;

class NotificationHelper
{
    protected $db;
    // ✅ Firebase JSON Key File Path
    protected $keyFile = WRITEPATH . 'keys/mysocial-18989-firebase-adminsdk-fbsvc-a3c29ebfe4.json'; 

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * 🔐 JWT Based Access Token Generator (FCM V1)
     */
    private function getAccessToken()
    {
        if (!file_exists($this->keyFile)) {
            log_message('error', 'FCM Key file not found at: ' . $this->keyFile);
            return null;
        }

        $json = json_decode(file_get_contents($this->keyFile), true);
        $now = time();
        
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'iss'   => $json['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => $json['token_uri'],
            'iat'   => $now,
            'exp'   => $now + 3600
        ]);

        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $json['private_key'], 'SHA256');
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $json['token_uri']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt
        ]));
        
        $res = json_decode(curl_exec($ch), true);
        return $res['access_token'] ?? null;
    }

    /**
     * 🚀 Main Send Function: Database + FCM Trigger
     */
    public function send($receiverId, $actorId, $type, $notifiableType, $notifiableId, $metadata = [])
    {
        // Guard: Khud ko notification nahi bhejna
        if ($receiverId == $actorId) {
            return false;
        }

        // 👤 Actor (Sender) ka naam nikalna
        $actor = $this->db->table('users')->select('name, username')->where('id', $actorId)->get()->getRow();
        $actorName = 'Someone';
        if ($actor) {
            $actorName = !empty($actor->name) ? $actor->name : $actor->username;
        }

        // ✉️ Message format karna
        $message = $this->getDefaultMessage($type, $notifiableType);

        // 🛡️ Duplicate Prevention (Likes/Follows)
        if (in_array($type, ['like', 'follow', 'subscribe'])) {
            $existing = $this->db->table('notifications')
                ->where([
                    'user_id'         => $receiverId,
                    'actor_id'        => $actorId,
                    'type'            => $type,
                    'notifiable_id'   => $notifiableId,
                    'notifiable_type' => $notifiableType,
                    'is_read'         => 0
                ])
                ->get()
                ->getRow();

            if ($existing) {
                $this->db->table('notifications')
                    ->where('id', $existing->id)
                    ->update(['created_at' => date('Y-m-d H:i:s')]);
                
                $this->sendPush($receiverId, $actorName, $type, $message, $metadata);
                return true;
            }
        }

        // 📝 Database Entry
        $inserted = $this->db->table('notifications')->insert([
            'user_id'         => $receiverId,
            'actor_id'        => $actorId,
            'type'            => $type,
            'notifiable_type' => $notifiableType,
            'notifiable_id'   => $notifiableId,
            'message'         => $message,
            'metadata'        => !empty($metadata) ? json_encode($metadata) : null,
            'is_read'         => 0,
            'created_at'      => date('Y-m-d H:i:s')
        ]);

        if ($inserted) {
            $this->sendPush($receiverId, $actorName, $type, $message, $metadata);
        }

        return $inserted;
    }

    /**
     * 📲 Private function: FCM V1 Payload Logic
     */
    private function sendPush($receiverId, $actorName, $type, $message, $metadata)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return false;

        $receiver = $this->db->table('users')
            ->select('fcm_token')
            ->where('id', $receiverId)
            ->get()
            ->getRow();

        if (!$receiver || empty($receiver->fcm_token)) {
            return false;
        }

        $url = 'https://fcm.googleapis.com/v1/projects/mysocial-18989/messages:send';
        
        $payload = [
            'message' => [
                'token' => $receiver->fcm_token,
                
                // 🔔 NATIVE NOTIFICATION: Android System Tray ke liye
                'notification' => [
                    'title' => $actorName,
                    'body'  => $message
                ],

                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        // 🔥 FIXED: Mapped to 'messages' channel for status bar consistency
                        'channel_id' => 'messages' 
                    ]
                ],

                // ⚙️ DATA PAYLOAD: React Native (Frontend) ke logic ke liye
                'data' => [
                    'sender'   => 'mysocial_backend', 
                    'type'     => (string)$type,       
                    'title'    => (string)$actorName,  
                    'body'     => (string)$message,    
                    'author'   => (string)$actorName,  
                    'metadata' => is_array($metadata) ? json_encode($metadata) : (string)$metadata
                ]
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    /**
     * 📝 Helper: Default Message Text
     */
    private function getDefaultMessage($type, $entity)
    {
        $messages = [
            'like'      => "liked your $entity",
            'comment'   => "commented on your $entity",
            'follow'    => "started following you",
            'subscribe' => "subscribed to your channel",
            'mention'   => "mentioned you in a $entity",
        ];

        return $messages[$type] ?? "interacted with your content";
    }
}

