<?php

namespace App\Models;

use CodeIgniter\Model;

class HashtagModel extends Model
{
    protected $table            = 'hashtags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    
    // 🔥 FIXED: Column names match your DB
    protected $allowedFields    = ['tag', 'posts_count', 'created_at'];

    protected $useTimestamps = false; // Kyunki tere DB me updated_at nahi hai

    /**
     * 📈 Trending Tags Logic (Fixed Columns)
     */
    public function getTrending($limit = 10, $query = null)
    {
        $builder = $this->builder();
        $builder->select('tag as name, posts_count as usage_count'); // Alias use kiya taki frontend na toote
        
        if ($query) {
            $builder->like('tag', $query, 'after');
        }
        
        $builder->orderBy('posts_count', 'DESC');
        $builder->limit($limit);
        
        return $builder->get()->getResultArray();
    }
}
