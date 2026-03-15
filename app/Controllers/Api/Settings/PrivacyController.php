<?php

namespace App\Controllers\Api\Settings;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class PrivacyController extends BaseController
{
    use ResponseTrait;

    protected $privacyColumns = [
        'is_private',
        'show_activity_status',
        'allow_comments',
        'allow_mentions',
        'allow_dm_requests'
    ];

    public function handlePrivacy()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');

        if (!$currentUserId) {
            return $this->failUnauthorized('User ID is required');
        }

        // Logic: GET ke liye sirf fetch, POST ke liye update
        if (strtolower($this->request->getMethod()) === 'get') {
            return $this->getPreferences($currentUserId);
        }

        return $this->updatePreferences($currentUserId);
    }

    public function getBlockedList()
    {
        $currentUserId = $this->request->getHeaderLine('User-ID');
        if (!$currentUserId) {
            return $this->failUnauthorized('User ID is required');
        }

        $db = \Config\Database::connect();
        $builder = $db->table('blocks b');

        $builder->select('
            b.blocked_entity_id as entity_id, 
            b.blocked_type as entity_type, 
            b.created_at,
            (CASE 
                WHEN b.blocked_type = "user" THEN u.name 
                WHEN b.blocked_type = "channel" THEN c.name 
            END) as display_name,
            (CASE 
                WHEN b.blocked_type = "user" THEN u.avatar 
                WHEN b.blocked_type = "channel" THEN c.avatar 
            END) as profile_image
        ', false);

        $builder->join('users u', 'u.id = b.blocked_entity_id AND b.blocked_type = "user"', 'left');
        $builder->join('channels c', 'c.id = b.blocked_entity_id AND b.blocked_type = "channel"', 'left');
        
        $builder->where('b.blocker_id', $currentUserId);
        $builder->orderBy('b.created_at', 'DESC');

        $blockedList = $builder->get()->getResultArray();

        foreach ($blockedList as &$row) {
            $row['entity_id'] = (int)$row['entity_id'];
            $row['display_name'] = $row['display_name'] ?? 'Unknown';
            $row['profile_image'] = $row['profile_image'] ?? '';
        }

        return $this->respond([
            'success' => true,
            'blocked_list' => $blockedList
        ]);
    }

    private function getPreferences($userId)
    {
        $db = \Config\Database::connect();
        $userPrefs = $db->table('users')
                        ->select(implode(', ', $this->privacyColumns))
                        ->where('id', $userId)
                        ->get()
                        ->getRowArray();

        if (!$userPrefs) {
            return $this->failNotFound('User settings not found');
        }

        $formattedData = [];
        foreach ($userPrefs as $key => $value) {
            $formattedData[$key] = ($key === 'allow_comments') ?
                $value : (bool)(int)$value;
        }

        return $this->respond(['success' => true, 'preferences' => $formattedData]);
    }

    private function updatePreferences($userId)
    {
        $json = $this->request->getJSON(true);
        
        if (empty($json)) {
            return $this->fail('Invalid input: JSON body is empty', 400);
        }

        $updateData = [];
        foreach ($json as $key => $value) {
            if (in_array($key, $this->privacyColumns)) {
                $updateData[$key] = ($key === 'allow_comments') ?
                    $value : (filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
            }
        }

        if (empty($updateData)) {
            return $this->fail('No valid fields to update');
        }

        $db = \Config\Database::connect();
        $db->table('users')->where('id', $userId)->update($updateData);

        return $this->respond(['success' => true, 'message' => 'Settings updated']);
    }
}
