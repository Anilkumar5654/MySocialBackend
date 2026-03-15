<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Stories extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        
        // Helpers load kiye (Permission, Media, Time sab zaroori hain)
        helper(['text', 'url', 'number', 'media', 'time', 'permission_helper']); 
    }

    /**
     * 1. LIST GROUPED STORIES (Index Page)
     * Ek User ka Ek Bubble dikhega (Instagram Style)
     */
    public function index()
    {
        // 🔒 Security Check
        if (!has_permission('stories.view')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $status = $this->request->getGet('status') ?? 'active'; 
        $search = $this->request->getGet('search');

        // 🔥 Logic: Group By User ID
        // Ek user ki multiple stories ho sakti hain, par hum index par user ko ek baar hi dikhayenge
        $builder = $this->db->table('stories');
        $builder->select('
            stories.user_id, 
            MAX(stories.created_at) as latest_activity,
            COUNT(stories.id) as story_count,
            users.username, 
            users.name as full_name, 
            users.avatar as user_avatar
        ');
        $builder->join('users', 'users.id = stories.user_id', 'left');

        // Time Logic (24 Hours)
        $time24hAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));
        
        if ($status == 'active') {
            $builder->where('stories.created_at >=', $time24hAgo);
        } elseif ($status == 'expired') {
            $builder->where('stories.created_at <', $time24hAgo);
        }

        if (!empty($search)) {
            $builder->like('users.username', $search);
        }

        // Grouping Apply ki taaki User repeat na ho
        $builder->groupBy('stories.user_id');
        $builder->orderBy('latest_activity', 'DESC');

        $data = [
            'title'   => "Stories Management", 
            'users'   => $builder->get()->getResult(), 
            'status'  => $status
        ];

        return view('admin/stories/index', $data);
    }

    /**
     * 2. VIEW USER'S STORIES (Gallery Page)
     * Yahan par Caption, View Count aur Active/Expired Filter bhi chalega
     */
    public function view($user_id)
    {
        // 🔒 Security Check
        if (!has_permission('stories.view')) {
            return redirect()->to('admin/dashboard');
        }

        // 1. Get Filter Status (Default: Active)
        $status = $this->request->getGet('status') ?? 'active';

        // 2. Fetch User Details
        $user = $this->db->table('users')->where('id', $user_id)->get()->getRow();
        if (!$user) return redirect()->to('admin/stories')->with('error', 'User not found');

        // 3. Prepare Query
        $builder = $this->db->table('stories');
        // Subquery for View Count included
        $builder->select('
            stories.*, 
            (SELECT COUNT(*) FROM story_views WHERE story_views.story_id = stories.id) as view_count
        ');
        $builder->where('user_id', $user_id);

        // 4. Apply Time Filter (Active vs Expired Logic)
        $time24hAgo = date('Y-m-d H:i:s', strtotime('-24 hours'));

        if ($status == 'active') {
            $builder->where('created_at >=', $time24hAgo);
        } elseif ($status == 'expired') {
            $builder->where('created_at <', $time24hAgo);
        }

        $builder->orderBy('created_at', 'DESC');
        
        $stories = $builder->get()->getResult();

        return view('admin/stories/view', [
            'user'    => $user,
            'stories' => $stories,
            'status'  => $status // View file ko bata rahe hain ki abhi konsa filter active hai
        ]);
    }

    /**
     * 3. DELETE SINGLE STORY
     */
    public function delete($id)
    {
        if (!has_permission('stories.delete')) {
            return redirect()->back()->with('error', 'Permission Denied');
        }

        $story = $this->db->table('stories')->where('id', $id)->get()->getRow();

        if ($story) {
            // 1. Physical File Delete
            // Helper config ke hisaab se folder 'stories' hai
            $filePath = FCPATH . 'upload/stories/' . $story->media_url;
            
            if (file_exists($filePath)) {
                @unlink($filePath);
            }

            // 2. Database Cleanup
            $this->db->transStart();
            $this->db->table('story_views')->where('story_id', $id)->delete(); // Pehle views udao
            $this->db->table('stories')->where('id', $id)->delete(); // Phir story udao
            $this->db->transComplete();
        }

        return redirect()->back()->with('success', 'Story deleted successfully.');
    }
    
    /**
     * 4. DELETE ALL STORIES OF A USER
     * (Jab "Clear All" button dabaya jaye)
     */
    public function delete_all($user_id)
    {
         if (!has_permission('stories.delete')) {
             return redirect()->back()->with('error', 'Permission Denied');
         }
         
         // Get all stories of this user (Active + Expired sab udayega)
         $stories = $this->db->table('stories')->where('user_id', $user_id)->get()->getResult();
         
         foreach($stories as $story){
             // Delete File
             $filePath = FCPATH . 'upload/stories/' . $story->media_url;
             if (file_exists($filePath)) { 
                 @unlink($filePath); 
             }
             
             // Delete Views
             $this->db->table('story_views')->where('story_id', $story->id)->delete();
         }
         
         // Delete Stories entries
         $this->db->table('stories')->where('user_id', $user_id)->delete();
         
         return redirect()->to('admin/stories')->with('success', 'All stories cleared for this user.');
    }
}
