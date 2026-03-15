<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminLogModel; 

class AdminLogs extends BaseController
{
    public function index()
    {
        // 🔒 SECURITY
        if (session()->get('role_id') != 1) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied: Only Super Admin can view logs.');
        }

        $request = service('request');
        $db = \Config\Database::connect();
        $logModel = new AdminLogModel();

        // Filters
        $filters = [
            'user_id' => $request->getGet('user_id'),
            'action'  => $request->getGet('action'),
            'date'    => $request->getGet('date')
        ];

        // Fetch Logs
        $logs = $logModel->getLogsWithUser($filters)->paginate(20);

        // 🔥 FIX: Fetch Admins WITH Role Name using JOIN
        $adminList = $db->table('users')
            ->select('users.id, users.name, users.username, admin_roles.role_name') // Role name select kiya
            ->join('admin_roles', 'admin_roles.id = users.role_id', 'left') // Table join ki
            ->where('users.role_id !=', 0)
            ->get()
            ->getResult();

        $data = [
            'title'   => 'System Activity Logs',
            'logs'    => $logs,
            'pager'   => $logModel->pager,
            'admins'  => $adminList, // ✅ Ab isme role_name hoga
            'request' => $request
        ];

        return view('admin/logs/index', $data);
    }
}
