<?php

/**
 * MySocial - Centralized Media Helper (FFmpeg & Permission Fixed)
 * Sabhi media types ka management ek hi jagah se (Cloud + Local Fallback).
 */

if (!function_exists('get_media_config')) {
    function get_media_config(string $type) {
        $datePath = date('Y/m/d');
        
        $config = [
            'reel'            => "reels/vid/{$datePath}/",
            'reel_thumbnail'  => "reels/thumb/{$datePath}/",
            'video'           => "videos/vid/{$datePath}/",
            'video_thumbnail' => "videos/thumb/{$datePath}/",
            'post_img'        => "posts/img/{$datePath}/",
            'story'           => "stories/img/{$datePath}/",
            'story_video'     => "stories/vid/{$datePath}/",
            'story_thumbnail' => "stories/thumb/{$datePath}/", 
            'ads_image'       => "ads/img/{$datePath}/",
            'ads_video'       => "ads/vid/{$datePath}/",
            'profile'         => "profiles/avatars/",
            'cover'           => "profiles/covers/",
            'channel_profile' => "channels/avatars/",
            'channel_cover'   => "channels/covers/",
            'chat_media'      => "chats/{$datePath}/",
            'kyc'             => "users/kyc/{$datePath}/",
        ];

        return $config[$type] ?? "others/{$datePath}/";
    }
}

if (!function_exists('prepare_upload_path')) {
    function prepare_upload_path(string $relativePath) {
        $basePath = FCPATH . 'uploads/'; 
        $fullPath = $basePath . $relativePath;

        if (!is_dir($fullPath)) {
            // Permission 0755 ensured for Anil user
            @mkdir($fullPath, 0755, true);
        }

        return $fullPath;
    }
}

/**
 * MASTER UPLOAD FUNCTION (UPGRADED FOR FFmpeg & PERMISSIONS)
 * $file: CI4 File Object OR String Path
 */
if (!function_exists('upload_media_master')) {
    function upload_media_master($file, string $type) {
        if (!$file) return null;

        // 🚀 STEP 1: Cloud Storage (DigitalOcean / S3)
        try {
            if (class_exists('\App\Libraries\CloudStorage')) {
                $cloud = new \App\Libraries\CloudStorage();
                $cloudResult = $cloud->upload($file, $type);

                if ($cloudResult && isset($cloudResult['success']) && $cloudResult['success'] === true && !empty($cloudResult['url'])) {
                    if (is_string($file) && file_exists($file)) {
                        @unlink($file); 
                    }
                    return $cloudResult['url'];
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Master Upload - Cloud Failed: ' . $e->getMessage());
        }

        // 🐢 STEP 2: Local Fallback
        $relPath   = get_media_config($type);
        $targetDir = prepare_upload_path($relPath);

        // Case A: Controller se aayi hui File (Uploaded File Object)
        if (is_object($file) && method_exists($file, 'isValid')) {
            if (!$file->isValid() || $file->hasMoved()) return null;
            
            $newName = $file->getRandomName();
            if ($file->move($targetDir, $newName)) {
                return $relPath . $newName;
            }
        } 
        // Case B: FFmpeg se aayi hui String Path (Thumbnails)
        elseif (is_string($file) && file_exists($file)) {
            // 🔥 FIXED: rename() ki jagah copy() use kiya taaki Cross-Device Link error na aaye
            $extension = pathinfo($file, PATHINFO_EXTENSION) ?: 'jpg';
            $newName   = bin2hex(random_bytes(8)) . '.' . $extension;
            $finalDestination = $targetDir . $newName;

            if (@copy($file, $finalDestination)) {
                @unlink($file); // Temp file saaf karein
                return $relPath . $newName;
            } else {
                log_message('error', "Local Copy Failed: From $file to $finalDestination");
            }
        }

        return null;
    }
}

if (!function_exists('delete_media_master')) {
    function delete_media_master($path) {
        if (empty($path)) return false;

        try {
            if (class_exists('\App\Libraries\CloudStorage')) {
                $cloud = new \App\Libraries\CloudStorage();
                $cloud->delete($path);
            }
        } catch (\Throwable $e) {}

        if (strpos($path, 'http') === false) {
            $fullPath = FCPATH . 'uploads/' . $path;
            if (file_exists($fullPath) && is_file($fullPath)) {
                return @unlink($fullPath);
            }
        }
        return true;
    }
}

if (!function_exists('get_media_url')) {
    function get_media_url($path) {
        if (empty($path) || $path === 'null' || strpos($path, 'default-placeholder') !== false) {
            return null; 
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        return base_url('uploads/' . $path);
    }
}
