<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminLogModel extends Model
{
    protected $table = 'admin_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'object'; 
    protected $allowedFields = ['user_id', 'action', 'target_id', 'target_type', 'note', 'ip_address', 'created_at'];

    // Custom function jo Users table ke saath Join lagata hai
    public function getLogsWithUser($filters = [])
    {
        $this->select('admin_logs.*, users.username, users.name, users.avatar, roles.role_name');
        $this->join('users', 'users.id = admin_logs.user_id', 'left');
        $this->join('admin_roles as roles', 'roles.id = users.role_id', 'left');

        // Apply Filters
        if (!empty($filters['user_id'])) {
            $this->where('admin_logs.user_id', $filters['user_id']);
        }
        if (!empty($filters['action'])) {
            $this->where('admin_logs.action', $filters['action']);
        }
        if (!empty($filters['date'])) {
            $this->like('admin_logs.created_at', $filters['date']);
        }

        return $this->orderBy('admin_logs.created_at', 'DESC');
    }
}
