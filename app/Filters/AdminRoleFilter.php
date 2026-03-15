<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminRoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Agar logged in nahi hai
        if (!$session->get('is_logged_in')) {
            return redirect()->to(base_url('admin/login'));
        }

        // Role Check (Optional Arguments)
        if (!empty($arguments)) {
            $userRole = $session->get('admin_role');
            if (!in_array($userRole, $arguments)) {
                return redirect()->to(base_url('admin/dashboard'))->with('error', 'Unauthorized');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
