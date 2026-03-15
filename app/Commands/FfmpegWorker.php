<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
// ✅ Helper class ko import kiya
use App\Helpers\IdGeneratorHelper;

class FfmpegWorker extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'ffmpeg:run';
    protected $description = 'Pro-Level Video Compression with Strict Copyright Action Logic';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        helper(['media', 'filesystem', 'url', 'trust_score_helper', 'copyright_helper']);

        // 1. Check System Enabled
        $ffmpegEnabled = $db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow();
        if (!$ffmpegEnabled || $ffmpegEnabled->setting_value !== 'true') return;

        // Reset stuck videos (30 mins limit)
        $timeoutLimit = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        $db->table('video_processing_queue')
           ->where('status', 'processing')
           ->where('updated_at <', $timeoutLimit)
           ->update(['status' => 'pending', 'error_message' => 'Resetting stuck process']);

        while (true) {
            $db->reconnect();

            $queueItem = $db->table('video_processing_queue')
                            ->where('status', 'pending')
                            ->orderBy('created_at', 'ASC')
                            ->get()->getRow();

            if (!$queueItem) {
                CLI::write("No more pending videos.", 'green');
                break; 
            }

            set_time_limit(0);
            CLI::write("------------------------------------------------", 'yellow');
            CLI::write("Processing ID: $queueItem->id | Type: $queueItem->video_type", 'cyan');
            
            $db->table('video_processing_queue')->where('id', $queueItem->id)->update([
                'status' => 'processing',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            // 2. Path Setup
            $inputPath = (filter_var($queueItem->input_path, FILTER_VALIDATE_URL)) 
                         ? $queueItem->input_path 
                         : FCPATH . 'public/uploads/' . $queueItem->input_path;

            if (!file_exists($inputPath) && !filter_var($inputPath, FILTER_VALIDATE_URL)) {
                $inputPath = FCPATH . 'uploads/' . $queueItem->input_path;
            }

            if (!file_exists($inputPath) && !filter_var($inputPath, FILTER_VALIDATE_URL)) {
                $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'failed', 'error_message' => 'Input missing']);
                continue; 
            }

            // 3. Dynamic Config
            $vidConfigKey = ''; $thumbConfigKey = ''; $dbTable = ''; $urlColumn = ''; $thumbColumn = '';
            $isStory = false;

            switch ($queueItem->video_type) {
                case 'reel':
                    $vidConfigKey = 'reel'; $thumbConfigKey = 'reel_thumbnail'; $dbTable = 'reels';
                    $urlColumn = 'video_url'; $thumbColumn = 'thumbnail_url';
                    break;
                case 'video':
                    $vidConfigKey = 'video'; $thumbConfigKey = 'video_thumbnail'; $dbTable = 'videos';
                    $urlColumn = 'video_url'; $thumbColumn = 'thumbnail_url';
                    break;
                case 'story':
                    $vidConfigKey = 'story_video'; $dbTable = 'stories'; $urlColumn = 'media_url'; 
                    $isStory = true;
                    break;
            }

            $tempProcessDir = FCPATH . 'uploads/temp/';
            if (!is_dir($tempProcessDir)) @mkdir($tempProcessDir, 0755, true);

            $outputName = 'proc_' . time() . '_' . uniqid() . '.mp4';
            $outputPath = $tempProcessDir . $outputName;

            // 4. Video Compression
            CLI::write("1. Compressing Video...", 'yellow');
            $cmdVideo = "ffmpeg -y -i " . escapeshellarg($inputPath) . " -vf \"scale=-2:720\" -c:v libx264 -preset medium -crf 28 -movflags +faststart -pix_fmt yuv420p -c:a aac -b:a 128k " . escapeshellarg($outputPath) . " 2>&1";
            exec($cmdVideo, $outputVideo, $returnVideo);
            
            if ($returnVideo === 0 && file_exists($outputPath)) {
                
                $videoHash = md5_file($outputPath);
                $frameHashesArray = [];
                $isMatchedInBlacklist = false;
                $finalAction = 'NONE';
                $originalVidId = null;

                if (!$isStory) { 
                    CLI::write("2. Fingerprinting & Copyright Scan...", 'yellow');
                    
                    $durCmd = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($outputPath);
                    $totalSec = (int) round(exec($durCmd));
                    
                    if ($totalSec > 0) {
                        $interval = $totalSec / 11;
                        for ($i = 1; $i <= 10; $i++) {
                            $seek = round($interval * $i);
                            $fPath = $tempProcessDir . 'f_tmp_' . $i . '_' . time() . '.jpg';
                            exec("ffmpeg -y -ss $seek -i " . escapeshellarg($outputPath) . " -vframes 1 -q:v 5 " . escapeshellarg($fPath) . " 2>&1");
                            if (file_exists($fPath)) {
                                $frameHashesArray[] = md5_file($fPath);
                                @unlink($fPath);
                            }
                        }
                    }
                    $finalFrameHashes = implode(',', $frameHashesArray);
                    $db->reconnect();

                    // --- 🛡️ A. BLACKLIST CHECK ---
                    $blacklist = $db->table('copyright_blacklist')->get()->getResult();
                    foreach ($blacklist as $banned) {
                        $bannedFrames = !empty($banned->frame_hashes) ? explode(',', $banned->frame_hashes) : [];
                        $matches = count(array_intersect($frameHashesArray, $bannedFrames));
                        if ($matches >= 7) { 
                            $isMatchedInBlacklist = true;
                            $originalVidId = $banned->original_video_id;
                            break;
                        }
                    }

                    // --- 🟢 LIVE DATABASE REMATCH ---
                    if (!$isMatchedInBlacklist) {
                        $existing = $db->table($dbTable)->select('id, frame_hashes')
                                       ->where('id !=', $queueItem->video_id)
                                       ->where('frame_hashes IS NOT NULL')
                                       ->get()->getResult();

                        foreach ($existing as $rec) {
                            $oldFrames = explode(',', $rec->frame_hashes);
                            $matches = count(array_intersect($frameHashesArray, $oldFrames));
                            if ($matches >= 7) { 
                                $isMatchedInBlacklist = true;
                                $originalVidId = $rec->id;
                                break;
                            }
                        }
                    }

                    // --- 🛡️ B. FETCH PREFERENCE & DECIDE ACTION ---
                    if ($isMatchedInBlacklist && $originalVidId) {
                        $originalVideo = $db->table($dbTable)->select('auto_match_action')->where('id', $originalVidId)->get()->getRow();
                        $finalAction = ($originalVideo) ? strtoupper($originalVideo->auto_match_action) : 'NONE';

                        if ($finalAction !== 'NONE') {
                            if (function_exists('handle_system_copyright_strike')) {
                                $db->reconnect();
                                handle_system_copyright_strike($queueItem->video_id, $queueItem->video_type, $queueItem->channel_id, $videoHash, $finalAction);
                            }

                            if ($finalAction === 'STRIKE') {
                                $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'failed', 'error_message' => 'Auto-Strike Applied']);
                                @unlink($outputPath); @unlink($inputPath);
                                continue;
                            }
                        }
                    }
                }

                // 5. Success Flow (Publishing)
                $db->reconnect();
                $finalVideoUrl = upload_media_master($outputPath, $vidConfigKey);
                $originalContent = $db->table($dbTable)->where('id', $queueItem->video_id)->get()->getRow();

                if ($finalVideoUrl) {
                    $finalStatus = 'published';
                    if ($originalContent && !empty($originalContent->scheduled_at)) {
                        if (strtotime($originalContent->scheduled_at) > (time() + 60)) $finalStatus = 'scheduled';
                    }

                    // ✅ INTEGRATION: Generate Smart Unique ID
                    $idGen = new IdGeneratorHelper();
                    $smartUniqueId = $idGen->generate($queueItem->video_type, $queueItem->video_id);

                    // ✅ FIX: Logic for Original Content ID (Original = NULL, Match = OriginalID)
                    $finalParentId = ($isMatchedInBlacklist && $originalVidId) ? $originalVidId : NULL;

                    $updateData = [
                        $urlColumn           => $finalVideoUrl,
                        'unique_id'          => $smartUniqueId, 
                        'original_content_id'=> $finalParentId, 
                        'status'             => $finalStatus, 
                        'video_hash'         => $videoHash,
                        'duration'           => $totalSec ?? 0
                    ];
                    
                    if (!$isStory) $updateData['frame_hashes'] = $finalFrameHashes;
                    if ($finalAction === 'CLAIM' && $isMatchedInBlacklist) {
                        $updateData['copyright_status'] = 'CLAIMED';
                    }

                    $db->transStart();
                    $db->table($dbTable)->where('id', $queueItem->video_id)->update($updateData);
                    
                    if (!$isStory && $thumbColumn && (empty($originalContent->$thumbColumn))) {
                        CLI::write("3. Generating Thumbnail...", 'yellow');
                        $tmpThumbPath = $tempProcessDir . 'thumb_' . $queueItem->video_id . '_' . time() . '.jpg';
                        exec("ffmpeg -y -i " . escapeshellarg($inputPath) . " -ss 00:00:01 -vframes 1 " . escapeshellarg($tmpThumbPath) . " 2>&1");
                        if (file_exists($tmpThumbPath) && filesize($tmpThumbPath) > 0) {
                            $finalThumbUrl = upload_media_master($tmpThumbPath, $thumbConfigKey);
                            if ($finalThumbUrl) {
                                $db->table($dbTable)->where('id', $queueItem->video_id)->update([$thumbColumn => $finalThumbUrl]);
                            }
                            @unlink($tmpThumbPath);
                        }
                    }

                    $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'completed']);
                    $db->transComplete();

                    @unlink($inputPath); @unlink($outputPath);
                    CLI::write("Successfully Processed! Unique ID: $smartUniqueId", 'green');
                }
            } else {
                $db->reconnect();
                $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'failed', 'error_message' => 'FFmpeg Error']);
            }
            CLI::write("------------------------------------------------", 'yellow');
        } 
    }
}
