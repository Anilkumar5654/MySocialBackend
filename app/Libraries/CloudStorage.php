<?php

namespace App\Libraries;

/**
 * MySocial - Cloud Storage Library (Clean Version)
 * Fixed: Removed span tags and syntax errors
 */
class CloudStorage
{
    protected $db;
    protected $settings = [];
    protected $s3 = null;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper('media');
        $this->initialize();
    }

    private function initialize()
    {
        $builder = $this->db->table('system_settings');
        $builder->whereIn('setting_key', [
            'cloud_storage_enabled',
            'do_bucket_name', 
            'do_region', 
            'do_endpoint', 
            'do_access_key', 
            'do_secret_key', 
            'do_cdn_enabled'
        ]);
        $query = $builder->get()->getResultArray();

        foreach ($query as $row) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }

        if (($this->settings['cloud_storage_enabled'] ?? 'false') !== 'true') {
            log_message('error', '🛑 CLOUD STATUS: Switch is OFF via Admin Panel.');
            return; 
        }

        if (!class_exists('\Aws\S3\S3Client')) {
            log_message('error', '⚠️ CLOUD STATUS: AWS Library Missing.');
            return;
        }

        if (!empty($this->settings['do_access_key']) && !empty($this->settings['do_secret_key'])) {
            try {
                $this->s3 = new \Aws\S3\S3Client([
                    'version' => 'latest',
                    'region'  => $this->settings['region'] ?? $this->settings['do_region'],
                    'endpoint' => $this->settings['do_endpoint'],
                    'credentials' => [
                        'key'    => $this->settings['do_access_key'],
                        'secret' => $this->settings['do_secret_key'],
                    ],
                    'use_path_style_endpoint' => false,
                ]);
            } catch (\Exception $e) {
                log_message('error', '❌ CLOUD CONNECTION ERROR: ' . $e->getMessage());
                $this->s3 = null;
            }
        }
    }

    public function upload($file, string $type)
    {
        if (!$this->s3) return false;

        try {
            $relativePath = get_media_config($type);
            $fileName = '';
            $sourcePath = '';
            $mimeType = '';
            $fileSize = 0;

            if (is_object($file) && method_exists($file, 'getTempName')) {
                $fileName   = $file->getRandomName();
                $sourcePath = $file->getTempName();
                $mimeType   = $file->getMimeType();
                $fileSize   = $file->getSize();
            } 
            elseif (is_string($file) && file_exists($file)) {
                $fileName   = basename($file);
                $sourcePath = $file;
                $mimeType   = mime_content_type($file);
                $fileSize   = filesize($file);
            } else {
                return false;
            }

            $fullPath = $relativePath . $fileName;

            $result = $this->s3->putObject([
                'Bucket' => $this->settings['do_bucket_name'],
                'Key'    => $fullPath,
                'Body'   => fopen($sourcePath, 'rb'),
                'ACL'    => 'public-read',
                'ContentType'  => $mimeType,
                'CacheControl' => 'max-age=31536000'
            ]);

            $fileUrl = $result['ObjectURL'];

            if (isset($this->settings['do_cdn_enabled']) && $this->settings['do_cdn_enabled'] === 'true') {
                $fileUrl = str_replace('digitaloceanspaces.com', 'cdn.digitaloceanspaces.com', $fileUrl);
            }

            return [
                'success' => true,
                'url'     => $fileUrl,
                'path'    => $fullPath,
                'mime'    => $mimeType,
                'size'    => $fileSize
            ];
        } catch (\Exception $e) {
            log_message('error', 'Cloud Upload Error: ' . $e->getMessage());
            return false;
        }
    }

    public function delete(string $filePath)
    {
        if (!$this->s3 || empty($filePath)) return false;
        if (strpos($filePath, 'http') === 0) {
            $parsed = parse_url($filePath);
            if (isset($parsed['path'])) {
                $filePath = ltrim($parsed['path'], '/');
                $bucketName = $this->settings['do_bucket_name'];
                $filePath = str_replace($bucketName . '/', '', $filePath);
            }
        }

        try {
            $this->s3->deleteObject([
                'Bucket' => $this->settings['do_bucket_name'],
                'Key'    => $filePath
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
