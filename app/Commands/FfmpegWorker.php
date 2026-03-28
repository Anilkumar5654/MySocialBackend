<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Helpers\IdGeneratorHelper;
use App\Helpers\NotificationHelper; 

class FfmpegWorker extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'ffmpeg:run';
    protected $description = 'ULTIMATE Production-Ready Video Worker - Numbered Logic Version';

    // 🟢 [LOGIC 1] - DEBUGGER SYSTEM
    private function logDebug($message) {
        $logFile = WRITEPATH . 'logs/ffmpeg_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }

    public function run(array $params)
    {
        // 🟢 [LOGIC 0] - OVERLAP PROTECTION (LOCK FILE)
        $lockFilePath = WRITEPATH . 'ffmpeg_worker.lock';
        $lockFile = fopen($lockFilePath, 'w+');
        if (!flock($lockFile, LOCK_EX | LOCK_NB)) {
            CLI::write("FFmpeg Worker is already running. Exiting safely.", 'yellow');
            return;
        }

        // 🟢 [LOGIC 2] - ENVIRONMENT STABILIZATION
        chdir(ROOTPATH);
        set_time_limit(0); 
        ini_set('memory_limit', '1024M'); 
        
        $db = \Config\Database::connect();
        helper(['media', 'filesystem', 'url', 'trust_score_helper', 'copyright_helper']);

        // 🟢 [LOGIC 3] - SYSTEM SWITCH CHECK
        $ffmpegEnabled = $db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow();
        if (!$ffmpegEnabled || $ffmpegEnabled->setting_value !== 'true') {
            CLI::write("FFmpeg is disabled in system settings.", 'red');
            $this->logDebug("CRITICAL: FFmpeg is disabled in system_settings table.");
            flock($lockFile, LOCK_UN); // Release lock
            return;
        }

        // 🟢 [LOGIC 4] - SELF-HEALING ENGINE
        $timeoutLimit = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        $db->table('video_processing_queue')
           ->where('status', 'processing')
           ->where('updated_at <', $timeoutLimit)
           ->update(['status' => 'pending', 'error_message' => 'Self-Healed: Connection Timeout']);

        // 🟢 [LOGIC 5] - PATH DISCOVERY
        $ffmpegPath = is_executable("/usr/bin/ffmpeg") ? "/usr/bin/ffmpeg" : trim(shell_exec("which ffmpeg"));
        if (empty($ffmpegPath)) $ffmpegPath = "/usr/bin/ffmpeg";

        while (true) {
            $this->ensureDbConnection($db);

            $queueItem = $db->table('video_processing_queue')
                            ->where('status', 'pending')
                            ->orderBy('created_at', 'ASC')
                            ->get()->getRow();

            if (!$queueItem) {
                CLI::write("Queue Clear. Standing by...", 'green');
                break; 
            }

            CLI::write(">>> PROCESSING: $queueItem->video_type ID: $queueItem->video_id", 'cyan');
            $this->logDebug("STARTING JOB: Queue ID $queueItem->id | Type: $queueItem->video_type | Target ID: $queueItem->video_id");
            
            $db->table('video_processing_queue')->where('id', $queueItem->id)->update([
                'status' => 'processing',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $dbTable = ($queueItem->video_type === 'reel') ? 'reels' : (($queueItem->video_type === 'story') ? 'stories' : 'videos');
            $targetId = (int)$queueItem->video_id;
            $originalVideo = $db->table($dbTable)->where('id', $targetId)->get()->getRow();
            
            if (!$originalVideo) {
                $this->logDebug("CRITICAL: Original record not found in $dbTable for ID $targetId");
                $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'failed', 'error_message' => 'Original missing']);
                continue;
            }

            $keepMonetization = (isset($originalVideo->monetization_enabled)) ? (int)$originalVideo->monetization_enabled : 0;
            $inputPath = (filter_var($queueItem->input_path, FILTER_VALIDATE_URL)) ? $queueItem->input_path : ROOTPATH . 'public/uploads/' . $queueItem->input_path;

            if (!file_exists($inputPath)) {
                $this->logDebug("ERROR: Input file missing at $inputPath");
                $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'failed', 'error_message' => 'Input missing']);
                continue; 
            }

            // 🟢 [LOGIC 6] - AUDIO & MUSIC HANDLER
            $mediaType = $queueItem->media_type ?? 'video';
            $musicId = $queueItem->music_id ?? null;
            $muteOriginal = (int)($queueItem->original_sound_muted ?? 0);
            
            $audioSourcePath = null;
            if (!empty($musicId)) {
                $music = $db->table('music')->where('id', $musicId)->get()->getRow();
                if ($music && !empty($music->audio_url)) {
                    $audioSourcePath = ROOTPATH . 'public/uploads/' . $music->audio_url;
                }
            }

            $urlCol  = ($queueItem->video_type === 'story') ? 'media_url' : 'video_url';
            $thumbCol = ($queueItem->video_type === 'story') ? null : 'thumbnail_url';
            $vidConfigKey = ($queueItem->video_type === 'story') ? 'story_video' : $queueItem->video_type;
            $thumbConfigKey = ($queueItem->video_type === 'reel') ? 'reel' : 'video_thumbnail';

            $tempDir = ROOTPATH . 'public/uploads/temp/';
            if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
            $outputPath = $tempDir . 'proc_' . time() . '_' . uniqid() . '.mp4';

            // 🟢 [LOGIC 7] - FFMPEG CORE ENGINE (STEP 1)
            if ($mediaType === 'image') {
                if ($audioSourcePath && file_exists($audioSourcePath)) {
                    $cmdVideo = "$ffmpegPath -y -loop 1 -t 15 -i " . escapeshellarg($inputPath) . " -i " . escapeshellarg($audioSourcePath) . " -c:v libx264 -preset ultrafast -tune stillimage -crf 28 -pix_fmt yuv420p -c:a aac -b:a 128k -shortest -vf \"scale=720:1280:force_original_aspect_ratio=decrease,pad=720:1280:(ow-iw)/2:(oh-ih)/2\" " . escapeshellarg($outputPath) . " 2>&1";
                } else {
                    $cmdVideo = "$ffmpegPath -y -loop 1 -t 15 -i " . escapeshellarg($inputPath) . " -c:v libx264 -preset ultrafast -tune stillimage -crf 28 -pix_fmt yuv420p -vf \"scale=720:1280:force_original_aspect_ratio=decrease,pad=720:1280:(ow-iw)/2:(oh-ih)/2\" " . escapeshellarg($outputPath) . " 2>&1";
                }
            } else {
                if ($audioSourcePath && file_exists($audioSourcePath)) {
                    $cmdVideo = "$ffmpegPath -y -i " . escapeshellarg($inputPath) . " -i " . escapeshellarg($audioSourcePath) . " -map 0:v:0 -map 1:a:0 -c:v libx264 -preset ultrafast -crf 28 -pix_fmt yuv420p -c:a aac -b:a 128k -shortest -vf \"scale=-2:720\" " . escapeshellarg($outputPath) . " 2>&1";
                } elseif ($muteOriginal === 1) {
                    $cmdVideo = "$ffmpegPath -y -i " . escapeshellarg($inputPath) . " -an -c:v libx264 -preset ultrafast -crf 28 -pix_fmt yuv420p -vf \"scale=-2:720\" " . escapeshellarg($outputPath) . " 2>&1";
                } else {
                    $cmdVideo = "$ffmpegPath -y -i " . escapeshellarg($inputPath) . " -vf \"scale=-2:720\" -c:v libx264 -preset ultrafast -crf 28 -movflags +faststart -pix_fmt yuv420p -c:a aac -b:a 128k " . escapeshellarg($outputPath) . " 2>&1";
                }
            }

            $this->logDebug("FFMPEG COMMAND: $cmdVideo");
            exec($cmdVideo, $outputVideo, $returnVideo);
            
            if ($returnVideo === 0 && file_exists($outputPath)) {
                $this->ensureDbConnection($db);
                $videoHash = md5_file($outputPath);
                $totalSec = $this->getVideoDuration($outputPath);

                // 🟢 [LOGIC 8] - COPYRIGHT SCAN ENGINE (STEP 2)
                $finalFrameHashes = ""; $isMatchedInBlacklist = false; $finalAction = 'NONE'; $originalVidId = null;
                if ($queueItem->video_type !== 'story') { 
                    $frameHashesArray = [];
                    if ($totalSec > 0) {
                        $interval = $totalSec / 11;
                        for ($i = 1; $i <= 10; $i++) {
                            $seek = round($interval * $i);
                            $fPath = $tempDir . 'f_tmp_' . $i . '_' . time() . '.jpg';
                            exec("$ffmpegPath -y -ss $seek -i " . escapeshellarg($outputPath) . " -vframes 1 -q:v 5 " . escapeshellarg($fPath) . " 2>&1");
                            if (file_exists($fPath)) { $frameHashesArray[] = md5_file($fPath); @unlink($fPath); }
                        }
                    }
                    $finalFrameHashes = implode(',', $frameHashesArray);
                    $blacklist = $db->table('copyright_blacklist')->get()->getResult();
                    foreach ($blacklist as $banned) {
                        $bannedFrames = !empty($banned->frame_hashes) ? explode(',', $banned->frame_hashes) : [];
                        if (count(array_intersect($frameHashesArray, $bannedFrames)) >= 7) { 
                            $isMatchedInBlacklist = true; $originalVidId = $banned->original_video_id; break; 
                        }
                    }
                    if ($isMatchedInBlacklist && $originalVidId) {
                        $orig = $db->table($dbTable)->select('auto_match_action')->where('id', $originalVidId)->get()->getRow();
                        $finalAction = $orig ? strtoupper($orig->auto_match_action) : 'NONE';
                        if ($finalAction !== 'NONE' && function_exists('handle_system_copyright_strike')) {
                            $this->logDebug("COPYRIGHT MATCH: Action=$finalAction | TargetID=$targetId");
                            handle_system_copyright_strike($targetId, $queueItem->video_type, $queueItem->channel_id, $videoHash, $finalAction);
                            if ($finalAction === 'STRIKE') {
                                $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'failed', 'error_message' => 'Copyright Strike']);
                                @unlink($outputPath); @unlink($inputPath); continue;
                            }
                        }
                    }
                }

                // 🟢 [LOGIC 9] - THUMBNAIL GENERATOR (STEP 3)
                $finalThumb = null;
                if ($thumbCol && empty($originalVideo->thumbnail_url)) {
                    $tmpThumb = $tempDir . 'thumb_' . $targetId . '_' . time() . '.jpg';
                    exec("$ffmpegPath -y -ss 00:00:00.500 -i " . escapeshellarg($outputPath) . " -vframes 1 -q:v 2 " . escapeshellarg($tmpThumb) . " 2>&1");
                    if (file_exists($tmpThumb)) { $finalThumb = upload_media_master($tmpThumb, $thumbConfigKey); @unlink($tmpThumb); }
                }

                // 🟢 [LOGIC 10] - FINALIZATION & PUBLISHING (STEP 4)
                $this->ensureDbConnection($db);
                $finalVideoUrl = upload_media_master($outputPath, $vidConfigKey);

                if ($finalVideoUrl) {
                    $idGen = new IdGeneratorHelper();
                    $dbFields = $db->getFieldNames($dbTable);
                    $finalMediaType = ($mediaType === 'image' && !empty($musicId)) ? 'video' : $mediaType;

                    $finalStatus = (isset($originalVideo->status) && $originalVideo->status === 'scheduled') ? 'scheduled' : 'published';

                    $updateData = [
                        $urlCol      => $finalVideoUrl,
                        'status'     => $finalStatus, 
                        'duration'   => $totalSec,
                        'updated_at' => gmdate('Y-m-d H:i:s')
                    ];

                    if ($queueItem->video_type === 'story' && in_array('expires_at', $dbFields)) {
                        $updateData['expires_at'] = $originalVideo->expires_at; 
                    }
                    
                    if ($queueItem->video_type === 'video' && !in_array('media_type', $dbFields)) {
                        unset($updateData['media_type']);
                    }

                    if ($queueItem->video_type === 'story' && !in_array('updated_at', $dbFields)) {
                        unset($updateData['updated_at']);
                    }

                    if (in_array('unique_id', $dbFields)) $updateData['unique_id'] = $idGen->generate($queueItem->video_type, $targetId);
                    if (in_array('video_hash', $dbFields)) $updateData['video_hash'] = $videoHash;
                    if (in_array('monetization_enabled', $dbFields)) $updateData['monetization_enabled'] = ($finalAction === 'CLAIM') ? 0 : $keepMonetization;
                    if (in_array('original_content_id', $dbFields)) $updateData['original_content_id'] = ($isMatchedInBlacklist) ? $originalVidId : NULL;
                    if (in_array('frame_hashes', $dbFields) && $queueItem->video_type !== 'story') $updateData['frame_hashes'] = $finalFrameHashes;
                    if (in_array('copyright_status', $dbFields) && $finalAction === 'CLAIM' && $isMatchedInBlacklist) $updateData['copyright_status'] = 'CLAIMED';
                    if ($thumbCol && $finalThumb && empty($originalVideo->thumbnail_url)) $updateData[$thumbCol] = $finalThumb;

                    $db->transBegin();
                    try {
                        $db->table($dbTable)->where('id', $targetId)->update($updateData);
                        
                        $db->table('video_processing_queue')->where('id', (int)$queueItem->id)->update([
                            'status' => 'completed',
                            'updated_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        $db->transCommit();
                        $this->logDebug("PUBLISHED SUCCESS: Table=$dbTable | ID=$targetId | Status=$finalStatus");
                        CLI::write("✅ ATTEMPT FINISHED: ID $targetId (Status: $finalStatus)", 'green');

                        if ($queueItem->video_type === 'video' && $finalStatus === 'published') {
                            $notifier = new NotificationHelper();
                            $notifier->notifySubscribersOnUpload($originalVideo->user_id, $targetId, 'video', $originalVideo->title, ['thumbnail' => $finalThumb]);
                        }
                    } catch (\Exception $e) {
                        $db->transRollback();
                        $this->logDebug("DB EXCEPTION: " . $e->getMessage());
                    }
                }
                @unlink($inputPath); @unlink($outputPath);
            } else {
                $db->table('video_processing_queue')->where('id', $queueItem->id)->update(['status' => 'failed', 'error_message' => 'FFmpeg Error']);
            }
        } 
        
        // Ensure lock is released at the end
        flock($lockFile, LOCK_UN);
    }

    private function ensureDbConnection($db) {
        try { if (!$db->query('SELECT 1')) $db->reconnect(); } catch (\Exception $e) { $db->reconnect(); }
    }

    private function getVideoDuration($path) {
        $ffmpegPath = is_executable("/usr/bin/ffmpeg") ? "/usr/bin/ffmpeg" : "ffmpeg";
        $ffprobePath = str_replace('ffmpeg', 'ffprobe', $ffmpegPath);
        $cmd = "$ffprobePath -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($path);
        return (int) round(exec($cmd));
    }
}
