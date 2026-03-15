<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RegenerateThumbnails extends BaseCommand
{
    protected $group       = 'Media';
    protected $name        = 'media:regenerate_thumbs';
    protected $description = 'Regenerate missing thumbnails for existing videos, reels, and stories.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        helper(['media', 'filesystem', 'url']);

        // Check if FFmpeg is enabled in settings
        $ffmpegEnabled = $db->table('system_settings')->where('setting_key', 'ffmpeg_enabled')->get()->getRow();
        if (!$ffmpegEnabled || $ffmpegEnabled->setting_value !== 'true') {
            CLI::error("Error: FFmpeg is disabled in System Settings. Enable it first.");
            return;
        }

        CLI::write("🚀 Starting Thumbnail Regeneration...", 'green');

        // Define Tables and Configuration
        $tables = [
            'reels' => [
                'id'        => 'id',
                'video_col' => 'video_url', 
                'thumb_col' => 'thumbnail_url', 
                'type'      => 'reel_thumbnail'
            ],
            'videos' => [
                'id'        => 'id',
                'video_col' => 'video_url', 
                'thumb_col' => 'thumbnail_url', 
                'type'      => 'video_thumbnail'
            ],
            'stories' => [
                'id'        => 'id',
                'video_col' => 'media_url', 
                'thumb_col' => 'thumbnail_url', // Ensure this column exists in stories table
                'type'      => 'story_thumbnail',
                'filter'    => "media_type = 'video'" // Special filter for stories
            ]
        ];

        foreach ($tables as $tableName => $config) {
            CLI::write("\n-------------------------------------------", 'yellow');
            CLI::write("🔍 Scanning Table: $tableName", 'yellow');

            // Check if table exists
            if (!$db->tableExists($tableName)) {
                CLI::error("   -> Table '$tableName' not found. Skipping.");
                continue;
            }

            // Check if thumbnail column exists
            if (!$db->fieldExists($config['thumb_col'], $tableName)) {
                CLI::error("   -> Column '{$config['thumb_col']}' missing in '$tableName'. Skipping.");
                continue;
            }

            // Build Query to find missing thumbnails
            $builder = $db->table($tableName);
            $builder->where("({$config['thumb_col']} IS NULL OR {$config['thumb_col']} = '')");
            
            // If stories, filter only videos
            if (isset($config['filter'])) {
                $builder->where($config['filter']);
            }

            $records = $builder->get()->getResultArray();
            $count = count($records);

            if ($count === 0) {
                CLI::write("   -> No missing thumbnails found.", 'cyan');
                continue;
            }

            CLI::write("   -> Found $count items. Processing...", 'cyan');

            $successCount = 0;
            $failCount = 0;

            foreach ($records as $row) {
                $id = $row['id'];
                $videoPathRaw = $row[$config['video_col']];

                if (empty($videoPathRaw)) {
                    $failCount++;
                    continue;
                }

                // Determine Input Path (Handle Cloud URLs or Local Paths)
                $inputPath = '';
                if (filter_var($videoPathRaw, FILTER_VALIDATE_URL)) {
                    $inputPath = $videoPathRaw; // Use URL directly (FFmpeg supports http/https)
                } else {
                    $inputPath = FCPATH . 'uploads/' . $videoPathRaw; // Local file
                }

                // Temp output path
                $tempName = 'regen_' . $tableName . '_' . $id . '_' . time() . '.jpg';
                $tempDir = FCPATH . 'uploads/temp/';
                if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
                $tempPath = $tempDir . $tempName;

                // FFmpeg Command: Take screenshot at 00:01
                // -y (overwrite), -ss 00:00:01 (seek), -vframes 1 (one image)
                $cmd = "ffmpeg -y -i " . escapeshellarg($inputPath) . " -ss 00:00:01 -vframes 1 " . escapeshellarg($tempPath) . " 2>&1";

                CLI::write("      Processing ID: $id...", 'white');
                exec($cmd, $output, $returnVar);

                if ($returnVar === 0 && file_exists($tempPath) && filesize($tempPath) > 0) {
                    // Upload to correct location (Cloud/Local)
                    $finalThumbUrl = upload_media_master($tempPath, $config['type']);

                    if ($finalThumbUrl) {
                        // Update Database
                        $db->table($tableName)->where('id', $id)->update([
                            $config['thumb_col'] => $finalThumbUrl
                        ]);
                        CLI::write("      ✔ Success! DB Updated.", 'green');
                        $successCount++;
                    } else {
                        CLI::error("      ✘ Upload Failed.");
                        $failCount++;
                    }
                    @unlink($tempPath); // Clean up temp file
                } else {
                    CLI::error("      ✘ FFmpeg Failed (Maybe invalid video file).");
                    // Optional: Log error output
                    // CLI::write(implode("\n", array_slice($output, -5)), 'red');
                    $failCount++;
                }
            }

            CLI::write("   -> Completed $tableName. Success: $successCount, Failed: $failCount", 'green');
        }

        CLI::write("\n✅ All Operations Completed!", 'green');
    }
}
