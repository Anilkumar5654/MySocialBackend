<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Posts extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // Helpers load kar rahe hain (Media helper zaroori hai)
        helper(['format_helper', 'permission_helper', 'text', 'url', 'number', 'media']);
    }

    /**
     * 1. LIST POSTS
     */
    public function index()
    {
        if (!has_permission('posts.view')) {
            return redirect()->to('admin/dashboard')->with('error', 'Access Denied');
        }

        $search = $this->request->getGet('search');
        $status = $this->request->getGet('status');
        $type   = $this->request->getGet('type');

        $builder = $this->db->table('posts');
        
        // 🔥 CRITICAL FIX: 'main_media' fetch karna zaroori hai
        // Kyunki 'posts' table me sirf caption hai, image 'post_media' me hai.
        $builder->select('
            posts.*, 
            users.username, 
            users.name as full_name, 
            users.avatar as user_avatar,
            (SELECT media_url FROM post_media WHERE post_id = posts.id ORDER BY display_order ASC LIMIT 1) as main_media
        ');
        
        $builder->join('users', 'users.id = posts.user_id', 'left');
        
        // Filters
        if (!empty($search)) {
            $builder->groupStart()
                    ->like('posts.content', $search)
                    ->orLike('users.username', $search)
                    ->groupEnd();
        }

        if (!empty($status)) {
            $builder->where('posts.status', $status);
        }

        if (!empty($type)) {
            $builder->where('posts.type', $type);
        }

        $data = [
            'title' => "Posts Management", 
            'posts' => $builder->orderBy('posts.created_at', 'DESC')->get()->getResult()
        ];

        return view('admin/posts/index', $data);
    }

    /**
     * 2. VIEW POST DETAILS
     */
    public function view($id)
    {
        if (!has_permission('posts.view')) return redirect()->to('admin/dashboard');

        // 🔥 FIX: Yahan bhi 'main_media' chahiye view page ke liye
        $post = $this->db->table('posts')
            ->select('
                posts.*, 
                users.username, 
                users.name as full_name, 
                users.avatar as user_avatar,
                (SELECT media_url FROM post_media WHERE post_id = posts.id ORDER BY display_order ASC LIMIT 1) as main_media
            ')
            ->join('users', 'users.id = posts.user_id', 'left')
            ->where('posts.id', $id)
            ->get()->getRow();

        if (!$post) return redirect()->to('admin/posts')->with('error', 'Post not found.');

        // Stats Logic
        $views    = $post->views_count > 0 ? $post->views_count : 1;
        $totalInteractions = $post->likes_count + $post->comments_count + $post->shares_count;
        $engagementRate = ($totalInteractions / $views) * 100;

        $rawViralScore = ($post->shares_count * 20) + ($post->comments_count * 10) + ($post->likes_count * 5) + ($views * 0.1);
        $finalScore = ($post->viral_score > 0) ? $post->viral_score : $rawViralScore;
        $viralPercentage = min(100, ($finalScore / 10000) * 100);

        // Fetch Recent Comments
        $comments = $this->db->table('comments')
            ->select('comments.*, users.username, users.avatar')
            ->join('users', 'users.id = comments.user_id', 'left')
            ->where('comments.commentable_id', $id)
            ->where('comments.commentable_type', 'post')
            ->orderBy('comments.id', 'DESC')
            ->limit(10)
            ->get()->getResult();

        return view('admin/posts/view', [
            'post'     => $post,
            'comments' => $comments,
            'stats'    => [
                'engagement_rate' => round($engagementRate, 2),
                'viral_score'     => round($finalScore, 0),
                'viral_percent'   => round($viralPercentage, 1)
            ]
        ]);
    }

    /**
     * 3. EDIT POST FORM
     */
    public function edit($id)
    {
        if (!has_permission('posts.edit')) return redirect()->to('admin/dashboard');
        
        // 🔥 FIX: Edit page par preview dikhane ke liye media chahiye
        $post = $this->db->table('posts')
            ->select('
                posts.*, 
                users.username, 
                users.name as full_name,
                (SELECT media_url FROM post_media WHERE post_id = posts.id ORDER BY display_order ASC LIMIT 1) as content
            ')
            ->join('users', 'users.id = posts.user_id', 'left')
            ->where('posts.id', $id)->get()->getRow();
            
        // Note: Upar query me maine 'main_media' ko 'content' alias diya hai 
        // kyunki edit view me hum $post->content use kar rahe hain media preview ke liye.
        // Agar text post hai to DB ka content column aayega, agar media hai to path aayega.
        
        // Better Approach for Edit:
        // Text posts ke liye 'content' column chahiye.
        // Media posts ke liye 'post_media' chahiye.
        
        // Let's refine the query specifically for Edit to avoid confusion
        $builder = $this->db->table('posts');
        $builder->select('posts.*, users.username');
        $builder->join('users', 'users.id = posts.user_id', 'left');
        $builder->where('posts.id', $id);
        $postRaw = $builder->get()->getRow();

        if (!$postRaw) return redirect()->to('admin/posts');

        // Agar media post hai, to content ko media path se replace kar do taaki view me dikhe
        if ($postRaw->type == 'photo' || $postRaw->type == 'video') {
            $media = $this->db->table('post_media')->where('post_id', $id)->orderBy('display_order', 'ASC')->get()->getRow();
            if ($media) {
                $postRaw->content = $media->media_url; // Override content with file path for display
            }
        }

        return view('admin/posts/edit', ['post' => $postRaw]);
    }

    /**
     * 4. UPDATE POST
     */
    public function update($id)
    {
        if (!has_permission('posts.edit')) return redirect()->back();
        
        // Sirf status, visibility aur text content update kar rahe hain.
        // File update karna complex hota hai, uske liye alag logic lagta hai.
        
        $data = [
            'status'     => $this->request->getPost('status'),
            'feed_scope' => $this->request->getPost('feed_scope'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Agar Text post hai to content update karo, media hai to nahi
        $postType = $this->db->table('posts')->select('type')->where('id', $id)->get()->getRow()->type;
        
        if ($postType == 'text') {
            $data['content'] = $this->request->getPost('content');
        }

        $this->db->table('posts')->where('id', $id)->update($data);
        
        return redirect()->to(base_url('admin/posts/view/'.$id))->with('success', 'Post Updated Successfully!');
    }

    /**
     * 5. DELETE POST (Physical File Cleanup Included)
     */
    public function delete($id)
    {
        if (!has_permission('posts.delete')) return redirect()->back();

        // 1. Media Files Fetch Karo
        $mediaItems = $this->db->table('post_media')->where('post_id', $id)->get()->getResult();

        // 2. Physical Files Delete Karo
        foreach ($mediaItems as $item) {
            // Helper config ke hisaab se folder map karo
            $subFolder = ($item->media_type == 'video') ? 'posts/videos' : 'posts/images';
            $filePath = FCPATH . 'upload/' . $subFolder . '/' . $item->media_url;

            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        // 3. Database Cleanup (Transaction safe)
        $this->db->transStart();
        $this->db->table('post_media')->where('post_id', $id)->delete();
        $this->db->table('likes')->where('likeable_id', $id)->where('likeable_type', 'post')->delete();
        $this->db->table('comments')->where('commentable_id', $id)->where('commentable_type', 'post')->delete();
        $this->db->table('saves')->where('saveable_id', $id)->where('saveable_type', 'post')->delete();
        $this->db->table('posts')->where('id', $id)->delete(); // Main Post
        $this->db->transComplete();

        return redirect()->to(base_url('admin/posts'))->with('success', 'Post and associated media deleted!');
    }

    /**
     * 6. DELETE COMMENT
     */
    public function delete_comment($id)
    {
        if (!has_permission('posts.edit')) return redirect()->back();
        $this->db->table('comments')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Comment deleted successfully.');
    }
}
