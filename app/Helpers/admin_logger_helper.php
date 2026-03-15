<?php

use CodeIgniter\HTTP\RequestInterface;

if (!function_exists('log_action')) {
    function log_action($action, $targetId, $targetType, $note = '')
    {
        $db = \Config\Database::connect();
        $request = \Config\Services::request();
        $session = \Config\Services::session();

        // 🔥 Fix: Ensure User ID is never NULL
        $userId = $session->get('user_id');
        
        // Agar user_id nahi mila (maybe key 'id' hai ya session expired hai)
        if (empty($userId)) {
            $userId = $session->get('id') ?? 0; // Fallback to 0 to prevent crash
        }

        $data = [
            'user_id'     => $userId, 
            'action'      => $action,
            'target_id'   => $targetId,
            'target_type' => $targetType,
            'note'        => $note, 
            'ip_address'  => $request->getIPAddress(),
            'created_at'  => date('Y-m-d H:i:s')
        ];

        $db->table('admin_logs')->insert($data);
    }
}
?>
