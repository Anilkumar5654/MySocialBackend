<?php

namespace App\Libraries;

class AdminAuth
{
    // Check karo ki kya user logged in aur admin hai
    public static function isAdmin()
    {
        $session = session();
        return ($session->get('is_logged_in') && $session->get('is_admin') == 1);
    }

    // Role check karne ke liye (e.g., 'super_admin', 'moderator')
    public static function hasRole($requiredRoles)
    {
        $session = session();
        $userRole = $session->get('admin_role');

        if (is_array($requiredRoles)) {
            return in_array($userRole, $requiredRoles);
        }

        return $userRole === $requiredRoles;
    }
}
