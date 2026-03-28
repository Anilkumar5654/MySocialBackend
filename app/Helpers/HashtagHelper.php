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
     * ✅ FINAL BULLETPROOF: Plain Tags Sync
     * Casting to (int) and (string) to ensure DB compatibility
     */
    public function syncPlainTags($contentId, $type, $tagsArray, $isVisible = 1)
    {
        // 1. Consistency: Type ko hamesha lowercase enum format me rakho
        $type = strtolower(trim($type));

        // 2. Fresh Sync: Purane links delete karo
        $this->db->table('taggables')->where([
            'taggable_id'   => (int)$contentId,
            'taggable_type' => (string)$type
        ])->delete();

        if (empty($tagsArray) || !is_array($tagsArray)) return true;

        try {
            foreach ($tagsArray as $tagName) {
                // Safai: lowercase aur hashes hatao
                $tagName = mb_strtolower(trim($tagName, '# ')); 
                if (empty($tagName)) continue;

                // 3. Insert Ignore in hashtags table
                $this->db->query("INSERT IGNORE INTO hashtags (tag, posts_count) VALUES (?, 0)", [$tagName]);

                // 4. Tag ki ID uthao
                $row = $this->db->table('hashtags')->where('tag', $tagName)->get()->getRow();
                
                if ($row) {
                    // 5. 🔥 STRICT CASTING: MySQL BIGINT aur ENUM ke liye safe approach
                    $this->db->table('taggables')->insert([
                        'hashtag_id'    => (int)$row->id,
                        'taggable_id'   => (int)$contentId,
                        'taggable_type' => (string)$type,
                        'is_visible'    => (int)$isVisible
                    ]);
                }
            }
            return true;
        } catch (\Exception $e) {
            log_message('error', '[Plain Tag Sync Error] ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ✅ RE-ENGINEERED: Controller interface
     */
    public function sync($type, $contentId, $tagsArray)
    {
        $tags = is_array($tagsArray) ? $tagsArray : [];
        return $this->syncPlainTags($contentId, $type, $tags);
    }

    /**
     * ✅ UNTOUCHED: Purana Regex Based Logic (Reels Caption ke liye)
     */
    public function syncHashtags($contentId, $type, $text, $isVisible = 1)
    {
        $type = strtolower(trim($type));

        if (empty($text)) {
            $this->db->table('taggables')->where([
                'taggable_id'   => (int)$contentId,
                'taggable_type' => (string)$type
            ])->delete();
            return true;
        }

        // Extract hashtags using Regex
        preg_match_all('/#(\w+)/u', $text, $matches);
        $tags = array_unique($matches[1]); 

        if (empty($tags)) {
            $this->db->table('taggables')->where([
                'taggable_id'   => (int)$contentId,
                'taggable_type' => (string)$type
            ])->delete();
            return true;
        }

        try {
            $this->db->table('taggables')->where([
                'taggable_id'   => (int)$contentId,
                'taggable_type' => (string)$type
            ])->delete();

            foreach ($tags as $tagName) {
                $tagName = mb_strtolower(trim($tagName));
                if (empty($tagName)) continue;

                $this->db->query("INSERT IGNORE INTO hashtags (tag, posts_count) VALUES (?, 0)", [$tagName]);
                $row = $this->db->table('hashtags')->where('tag', $tagName)->get()->getRow();
                
                if ($row) {
                    $this->db->table('taggables')->insert([
                        'hashtag_id'    => (int)$row->id,
                        'taggable_id'   => (int)$contentId,
                        'taggable_type' => (string)$type,
                        'is_visible'    => (int)$isVisible
                    ]);
                }
            }
            return true;
        } catch (\Exception $e) {
            log_message('error', '[Hashtag Regex Error] ' . $e->getMessage());
            return false;
        }
    }
}
