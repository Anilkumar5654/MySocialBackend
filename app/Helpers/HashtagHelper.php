<?php

namespace App\Helpers;

class HashtagHelper
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * 🔥 NEW: Seedhe Array (tags) ko handle karne ke liye
     * Isse Controller ka error khatam ho jayega
     */
    public function sync($type, $contentId, $tagsArray)
    {
        if (empty($tagsArray)) {
            return $this->syncHashtags($contentId, $type, ""); 
        }
        
        // Array ko text format mein convert karke syncHashtags ko bhej rahe hain
        // Taaki aapka purana logic aur # waale tags ka system na bigde
        $text = "";
        foreach($tagsArray as $t) {
            $t = trim($t, '# ');
            if (!empty($t)) {
                $text .= " #" . $t;
            }
        }
        return $this->syncHashtags($contentId, $type, $text);
    }

    /**
     * ✅ UNTOUCHED: Purana Regex Based Logic
     */
    public function syncHashtags($contentId, $type, $text, $isVisible = 1)
    {
        if (empty($text)) {
            // Agar text khali hai toh purane links delete
            $this->db->table('taggables')->where([
                'taggable_id'   => $contentId,
                'taggable_type' => $type
            ])->delete();
            return true;
        }

        // 1. Extract hashtags (Regex) - Ye # waale tags dhoondhta hai
        preg_match_all('/#(\w+)/u', $text, $matches);
        $tags = array_unique($matches[1]); 

        if (empty($tags)) {
            $this->db->table('taggables')->where([
                'taggable_id'   => $contentId,
                'taggable_type' => $type
            ])->delete();
            return true;
        }

        try {
            // 2. Purane links hatao
            $this->db->table('taggables')->where([
                'taggable_id'   => $contentId,
                'taggable_type' => $type
            ])->delete();

            foreach ($tags as $tagName) {
                $tagName = mb_strtolower(trim($tagName));
                if (empty($tagName)) continue;

                // 3. 🔥 TRIGGER-FRIENDLY INSERT
                $this->db->query("INSERT IGNORE INTO hashtags (tag, posts_count) VALUES (?, 0)", [$tagName]);

                // 4. Tag ID fetch karo
                $row = $this->db->table('hashtags')->where('tag', $tagName)->get()->getRow();
                
                if ($row) {
                    // 5. Insert hote hi SQL TRIGGER count +1 kar dega
                    $this->db->table('taggables')->insert([
                        'hashtag_id'    => $row->id,
                        'taggable_id'   => $contentId,
                        'taggable_type' => $type,
                        'is_visible'    => $isVisible
                    ]);
                }
            }
            return true;
        } catch (\Exception $e) {
            log_message('error', '[Hashtag Error] ' . $e->getMessage());
            return false;
        }
    }
}

