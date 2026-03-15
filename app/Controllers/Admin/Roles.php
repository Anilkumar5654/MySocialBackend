<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Roles extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Helpers loaded for security and logging
        helper(['form', 'url', 'permission_helper', 'admin_logs_helper']);
    }

    /**
     * 🔥 MASTER PERMISSION LIST
     * Fully Synchronized with Sidebar, Routes & Moderation
     */
    private $permissionMap = [
        'Staff Control' => ['roles.view', 'roles.manage', 'staff.view', 'staff.manage'],
        'User Management' => ['users.view', 'users.edit', 'users.ban', 'users.delete', 'kyc.view', 'kyc.action'],
        'Channel Management' => ['channels.view', 'channels.edit', 'channels.delete', 'channels.monetization'],
        'Stories Management' => ['stories.view', 'stories.edit', 'stories.delete'],
        'Reels Management' => ['reels.view', 'reels.edit', 'reels.delete'],
        'Posts Management' => ['posts.view', 'posts.edit', 'posts.delete'],
        'Content (Videos)' => ['videos.view', 'videos.create', 'videos.edit', 'videos.delete'],
        'Moderation & Strikes' => ['reports.manage', 'reports.action', 'strikes.view', 'strikes.manage', 'strikes.appeals', 'strikes.remove'],
        'Detailed Reports' => ['reports.videos.view', 'reports.reels.view', 'reports.posts.view', 'reports.comments.view', 'reports.users.view', 'reports.channels.view'],
        'Finance & Ads' => ['ads.view', 'ads.approve', 'ads.manage', 'ads.settings', 'ads.delete', 'payouts.manage', 'withdrawals.view', 'withdrawals.action'],
        'System Settings' => ['settings.edit']
    ];

    public function index()
    {
        if (!has_permission('roles.view')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $data = [
            'title' => 'Role Management',
            'roles' => $this->db->table('admin_roles')
                                ->where('id !=', 1) // Permanent Hide Super Admin
                                ->orderBy('id', 'ASC')
                                ->get()
                                ->getResult()
        ];
        
        return view('admin/roles/index', $data);
    }

    public function create()
    {
        if (!has_permission('roles.manage')) {
            return redirect()->to('admin/roles')->with('error', 'Unauthorized');
        }

        return view('admin/roles/create', [
            'title'       => 'Create New Role',
            'permissions' => $this->permissionMap
        ]);
    }

    public function store()
    {
        if (!has_permission('roles.manage')) return redirect()->back();

        // 1. Validation Logic
        $roleName = $this->request->getPost('role_name');
        $permissions = $this->request->getPost('permissions');

        if (empty($roleName)) {
            return redirect()->back()->with('error', 'Role name is required.');
        }

        $this->db->transStart();

        $roleData = [
            'role_name'   => esc($roleName),
            'description' => esc($this->request->getPost('description'))
        ];

        $this->db->table('admin_roles')->insert($roleData);
        $roleId = $this->db->insertID();

        if (!empty($permissions) && is_array($permissions)) {
            $batchData = [];
            foreach ($permissions as $perm) {
                $batchData[] = [
                    'role_id'         => $roleId,
                    'permission_slug' => esc($perm)
                ];
            }
            $this->db->table('admin_permissions')->insertBatch($batchData);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Database Error: Could not create role.');
        }

        // ✅ LOGGING: Kisne banaya aur kya role name tha
        log_action('CREATE_ROLE', $roleId, 'roles', "New Role: $roleName created.");

        return redirect()->to('admin/roles')->with('success', 'New Role Created Successfully');
    }

    public function edit($id)
    {
        if (!has_permission('roles.manage')) return redirect()->back();

        // 🛡️ SECURITY: Permanent lock on Super Admin
        if ($id == 1) {
            log_action('SECURITY_VIOLATION', $id, 'roles', 'Attempted to edit Super Admin role.');
            return redirect()->to('admin/roles')->with('error', 'Super Admin is protected by System.');
        }

        $role = $this->db->table('admin_roles')->where('id', $id)->get()->getRow();
        if (!$role) return redirect()->to('admin/roles')->with('error', 'Role not found');

        $dbPerms = $this->db->table('admin_permissions')->where('role_id', $id)->get()->getResultArray(); 
        $selectedPerms = array_column($dbPerms, 'permission_slug');

        return view('admin/roles/edit', [
            'title'          => 'Edit Role: ' . $role->role_name,
            'role'           => $role,
            'permissions'    => $this->permissionMap,
            'selected_perms' => $selectedPerms 
        ]);
    }

    public function update($id)
    {
        if (!has_permission('roles.manage')) return redirect()->back();
        if ($id == 1) return redirect()->back()->with('error', 'System Restricted: Cannot modify Super Admin.');

        $roleName = $this->request->getPost('role_name');
        $permissions = $this->request->getPost('permissions');

        $this->db->transStart();

        // Update Role Details
        $this->db->table('admin_roles')->where('id', $id)->update([
            'role_name'   => esc($roleName),
            'description' => esc($this->request->getPost('description')),
        ]);

        // Sync Permissions (Delete old, Insert new)
        $this->db->table('admin_permissions')->where('role_id', $id)->delete();

        if (!empty($permissions) && is_array($permissions)) {
            $batchData = [];
            foreach ($permissions as $perm) {
                $batchData[] = [
                    'role_id'         => $id,
                    'permission_slug' => esc($perm)
                ];
            }
            $this->db->table('admin_permissions')->insertBatch($batchData);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === FALSE) {
            return redirect()->back()->with('error', 'Update Failed: Transaction Error.');
        }

        // ✅ LOGGING
        log_action('UPDATE_ROLE', $id, 'roles', "Updated permissions for: $roleName");

        return redirect()->to('admin/roles')->with('success', 'Role Updated Successfully');
    }

    public function delete($id)
    {
        if (!has_permission('roles.manage')) return redirect()->back();
        if ($id == 1) return redirect()->back()->with('error', 'Critical Error: Super Admin cannot be deleted.');

        // Check for users using this role
        $userCount = $this->db->table('users')->where('role_id', $id)->countAllResults();
        if ($userCount > 0) {
            return redirect()->back()->with('error', "Constraint Error: Role assigned to $userCount active users.");
        }

        $this->db->transStart();
        // Cascading Manual Delete for safety
        $this->db->table('admin_permissions')->where('role_id', $id)->delete();
        $this->db->table('admin_roles')->where('id', $id)->delete();
        $this->db->transComplete();

        if ($this->db->transStatus() === TRUE) {
            log_action('DELETE_ROLE', $id, 'roles', "Role ID $id was permanently removed.");
            return redirect()->to('admin/roles')->with('success', 'Role and associated permissions removed.');
        }

        return redirect()->back()->with('error', 'Deletion Failed.');
    }
}

