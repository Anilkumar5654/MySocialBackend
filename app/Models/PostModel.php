<?php

namespace App\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table            = 'posts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'unique_id',      // ✅ Added for Smart ID
        'user_id',
        'type',          
        'content',       
        'location',
        'feed_scope',    
        'status',        
        'likes_count',
        'comments_count',
        'shares_count',
        'views_count',
        'viral_score'
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'unique_id'  => 'required|is_unique[posts.unique_id,id,{id}]', // ✅ Security Check
        'user_id'    => 'required|integer',
        'type'       => 'required|in_list[text,photo,video,short]',
        'feed_scope' => 'in_list[followers,public]',
        'status'     => 'in_list[draft,published,archived]',
        'content'    => 'permit_empty|string'
    ];

    protected $validationMessages = [
        'type' => ['in_list' => 'Invalid post type.'],
        'feed_scope' => ['in_list' => 'Feed scope must be public or followers.'],
        'unique_id' => ['is_unique' => 'Duplicate Smart ID detected.']
    ];

    public function getPostWithMedia($postId)
    {
        $post = $this->find($postId);
        if ($post) {
            $post['images'] = $this->db->table('post_media')
                                 ->where('post_id', $postId)
                                 ->orderBy('display_order', 'ASC')
                                 ->get()->getResultArray();
        }
        return $post;
    }
}

