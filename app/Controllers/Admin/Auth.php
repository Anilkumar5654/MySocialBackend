<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Auth extends BaseController
{
    public function loginView()
    {
        // Agar pehle se logged in hai toh dashboard bhej do
        if (session()->get('is_logged_in') && session()->get('is_admin')) {
            return redirect()->to('/admin/dashboard');
        }
        return view('admin/login'); 
    }

    public function login()
    {
        $session = session();
        
        // 1. Validation Rules
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check your email and password.');
        }
        
        $db = \Config\Database::connect();
        
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // User fetch karo
        $user = $db->table('users')->where('email', $email)->get()->getRow();

        if ($user && password_verify($password, $user->password)) {
            
            // Check: Kya yeh admin hai?
            if ($user->is_admin == 1) {
                
                // 🔥 NEW FIX: Permissions Load Karna (Separate Table Se)
                $permissions = [];
                
                if (!empty($user->role_id)) {
                    // Ab hum 'admin_permissions' table se data layenge
                    $permQuery = $db->table('admin_permissions')
                                    ->where('role_id', $user->role_id)
                                    ->get()
                                    ->getResultArray(); // Array of rows return karega

                    // Multi-dimensional array se sirf slugs ka simple array banayenge
                    // Result: ['users.view', 'videos.delete', 'reports.manage']
                    if (!empty($permQuery)) {
                        $permissions = array_column($permQuery, 'permission_slug');
                    }
                }

                // Session Data Set
                $session->set([
                    'id'            => $user->id,
                    'username'      => $user->username,
                    'email'         => $user->email,
                    'role_id'       => $user->role_id,
                    'is_admin'      => true,
                    'is_logged_in'  => true,
                    'admin_perms'   => $permissions // ✅ Ab ye sahi format mein session mein jayega
                ]);

                return redirect()->to('/admin/dashboard');
            }
            
            return redirect()->back()->with('error', 'Access Denied: You are not an Admin');
        }
        
        return redirect()->back()->with('error', 'Invalid Email or Password');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/admin/login');
    }
}
