<?php

/**
 * 🚀 JALEBI FIX: Optimized Permission Checker
 * Isme static variable use kiya hai taaki database load kam ho.
 */
function has_permission($permission_slug)
{
    // Static variable memory mein data save rakhta hai ek single request ke liye
    static $permissions_cache = null;

    $session = session();
    $role_id = $session->get('role_id');

    // 1. Agar login hi nahi hai, toh seedha false
    if (!$role_id) {
        return false;
    }

    // 2. Super Admin Bypass (Sabse fast check)
    if ($role_id == 1) {
        return true;
    }

    // 3. Database se permissions sirf EK BAAR load hongi per page load
    if ($permissions_cache === null) {
        $db = \Config\Database::connect();
        
        $results = $db->table('admin_permissions')
                      ->select('permission_slug')
                      ->where('role_id', $role_id)
                      ->get()
                      ->getResultArray();

        // Array_column se hum ise simple array ['users.view', 'roles.edit'] mein badal denge
        $permissions_cache = array_column($results, 'permission_slug');
    }

    // 4. In-array check memory mein hota hai, jo database se 1000x fast hai
    return in_array($permission_slug, $permissions_cache);
}

