<?php

namespace App\Controllers\Api\Settings;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class NotificationController extends BaseController
{
    use ResponseTrait;

    protected $notificationColumns = [
        'allow_global',        
        'allow_likes',         
        'allow_follows',       
        'allow_mentions',      
        'allow_video_uploads', 
        'allow_dm_requests',   
    ];

    public function handleNotifications()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');

        if (!$currentUserId) {
            return $this->failUnauthorized('User ID is required');
        }

        if (strtolower($this->request->getMethod()) === 'get') {
            return $this->getPreferences($currentUserId);
        }

        return $this->updatePreferences($currentUserId);
    }

    private function getPreferences($userId)
    {
        $db = \Config\Database::connect();
        $userPrefs = $db->table('users')
                        ->select(implode(', ', $this->notificationColumns))
                        ->where('id', $userId)
                        ->get()
                        ->getRowArray();

        if (!$userPrefs) {
            return $this->failNotFound('User settings not found.');
        }

        $formattedData = [];
        foreach ($userPrefs as $key => $value) {
            $formattedData[$key] = (bool)(int)$value;
        }

        return $this->respond([
            'success' => true,
            'preferences' => $formattedData
        ]);
    }

    private function updatePreferences($userId)
    {
        $json = $this->request->getJSON(true);

        if (empty($json)) {
            return $this->fail('Invalid JSON input: Body required', 400);
        }

        $updateData = [];
        foreach ($json as $key => $value) {
            if (in_array($key, $this->notificationColumns)) {
                $updateData[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            }
        }

        if (empty($updateData)) {
            return $this->respond([
                'success' => false,
                'message' => 'No valid fields provided.'
            ]);
        }

        $db = \Config\Database::connect();
        $db->table('users')->where('id', $userId)->update($updateData);

        return $this->respond([
            'success' => true,
            'message' => 'Notification settings updated.'
        ]);
    }
}
