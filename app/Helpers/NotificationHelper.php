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
     * 🚀 Main Send Function: STRICT FILTER & CLEAN TITLES
     */
    public function send($receiverId, $actorId, $type, $notifiableType, $notifiableId, $metadata = [])
    {
        if ($receiverId == $actorId) return false;

        // 🔥 STRICT FILTER: Sirf real activity types allow hain
        $allowedTypes = ['like', 'comment', 'follow', 'subscribe', 'mention', 'comment_like', 'new_upload'];
        if (!in_array($type, $allowedTypes)) return false; 

        $message = $this->getDefaultMessage($type, $notifiableType);
        
        // Agar metadata mein comment text hai, toh use bhi truncate karo
        if ($type === 'comment' && !empty($metadata['comment_text'])) {
            $shortComment = mb_strimwidth($metadata['comment_text'], 0, 40, "...");
            $message = "Commented: " . $shortComment;
        }

        $actor = $this->db->table('users')->select('name, username')->where('id', $actorId)->get()->getRow();
        $actorName = $actor ? (!empty($actor->name) ? $actor->name : $actor->username) : 'Someone';

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
            return true;
        }

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
            $this->sendPush($receiverId, $actorName, $type, $message, $notifiableId, $notifiableType, $metadata);
        }

        return $inserted;
    }

    /**
     * 📺 NOTIFY SUBSCRIBERS: Handle "new_upload" duplication & Shorten Title
     */
    public function notifySubscribersOnUpload($creatorId, $notifiableId, $notifiableType, $title, $metadata = [])
    {
        if ($notifiableType !== 'video') return false;

        $creator = $this->db->table('users')->select('name, username')->where('id', $creatorId)->get()->getRow();
        if (!$creator) return false;
        $actorName = !empty($creator->name) ? $creator->name : $creator->username;

        $subscribers = $this->db->table('follows')
            ->select('follower_id')
            ->where('following_id', $creatorId)
            ->whereIn('notification_pref', ['all', 'personalized'])
            ->get()
            ->getResultArray();

        if (empty($subscribers)) return false;

        // 🔥 FIX: Shorten Title if it's too long (Max 40 chars)
        $shortTitle = mb_strimwidth($title, 0, 40, "...");
        $message = "uploaded : " . $shortTitle;
        
        $count = 0;

        foreach ($subscribers as $sub) {
            $followerId = $sub['follower_id'];

            $exists = $this->db->table('notifications')
                ->where([
                    'user_id'       => $followerId,
                    'type'          => 'new_upload',
                    'notifiable_id' => $notifiableId,
                    'is_read'       => 0
                ])->countAllResults();

            if ($exists > 0) continue; 

            $this->db->table('notifications')->insert([
                'user_id'         => $followerId,
                'actor_id'        => $creatorId,
                'type'            => 'new_upload',
                'notifiable_type' => $notifiableType,
                'notifiable_id'   => $notifiableId,
                'message'         => $message,
                'metadata'        => !empty($metadata) ? json_encode($metadata) : null,
                'is_read'         => 0,
                'created_at'      => date('Y-m-d H:i:s')
            ]);

            $this->sendPush($followerId, $actorName, 'new_upload', $message, $notifiableId, $notifiableType, $metadata);
            $count++;
        }
        return $count;
    }

    private function sendPush($receiverId, $actorName, $type, $message, $notifiableId, $notifiableType, $metadata)
    {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) return false;

        $receiver = $this->db->table('users')->select('fcm_token')->where('id', $receiverId)->get()->getRow();
        if (!$receiver || empty($receiver->fcm_token)) return false;

        $url = 'https://fcm.googleapis.com/v1/projects/mysocial-18989/messages:send';
        $imageUrl = isset($metadata['thumbnail']) ? base_url('uploads/' . $metadata['thumbnail']) : null;

        $payload = [
            'message' => [
                'token' => $receiver->fcm_token,
                'notification' => [
                    'title' => (string)$actorName,
                    'body'  => (string)$message,
                    'image' => $imageUrl
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => ['sound' => 'default', 'channel_id' => 'messages']
                ],
                'data' => [
                    'type'            => (string)$type,       
                    'notifiable_id'   => (string)$notifiableId,
                    'notifiable_type' => (string)$notifiableType,
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_exec($ch);
        curl_close($ch);
    }

    private function getDefaultMessage($type, $entity)
    {
        $messages = [
            'like'         => "Liked",
            'comment'      => "Commented:",
            'follow'       => "Followed you",
            'subscribe'    => "Subscribed",
            'mention'      => "Mentioned you",
            'comment_like' => "Liked your comment",
            'new_upload'   => "uploaded : "
        ];
        
        // Agar type inme se nahi hai, toh false return karega send function mein
        return $messages[$type] ?? null; 
    }
}
