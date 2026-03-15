<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Staff extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // 🔥 FIX: Added 'media' helper for Avatars & 'permission_helper' for Auth checks
        helper(['format_helper', 'permission_helper', 'text', 'url', 'number', 'media']);
    }

    public function index()
    {
        // 🔒 Permission Check
        if (!has_permission('staff.view')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied.');
        }

        $search   = $this->request->getGet('search');
        $role     = $this->request->getGet('role');
        $status   = $this->request->getGet('status');

        $builder = $this->db->table('users');
        $builder->select('users.*, admin_roles.role_name');
        $builder->join('admin_roles', 'admin_roles.id = users.role_id', 'left');

        // 🔥 Logic: Show Admins OR Users with Role ID > 0
        $builder->groupStart()
                ->where('users.is_admin', 1)
                ->orWhere('users.role_id >', 0) 
                ->groupEnd();

        // Filters
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('users.name', $search)
                    ->orLike('users.username', $search)
                    ->orLike('users.email', $search)
                    ->groupEnd();
        }

        if (!empty($role)) {
            $builder->where('users.role_id', $role);
        }

        if ($status !== null && $status !== '') {
            $builder->where('users.is_banned', $status);
        }

        $data = [
            'title' => "Staff Management",
            'staff' => $builder->orderBy('users.is_admin', 'DESC')
                               ->orderBy('users.role_id', 'ASC')
                               ->get()
                               ->getResult(),
            'roles' => $this->db->table('admin_roles')->get()->getResult()
        ];

        return view('admin/staff/index', $data);
    }

    public function remove_admin($id)
    {
        if (!has_permission('staff.manage')) {
            return redirect()->back()->with('error', 'Unauthorized Action');
        }

        if ($id == session()->get('id')) {
            return redirect()->back()->with('error', 'You cannot remove yourself!');
        }

        if ($id == 1) {
            return redirect()->back()->with('error', 'Super Admin cannot be removed.');
        }

        // 🔥 Remove Staff Status (Make Normal User)
        $this->db->table('users')->where('id', $id)->update([
            'is_admin'   => 0,
            'role_id'    => 0, // Set to 0 (Normal User) instead of NULL to avoid DB errors if column is NOT NULL
            'admin_role' => null
        ]);

        return redirect()->to(base_url('admin/staff'))->with('success', 'Staff removed successfully.');
    }
}
