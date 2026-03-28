<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Helpers\NotificationHelper;

class AutoPublisherWorker extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'worker:publish';
    protected $description = 'Auto-publishes scheduled videos and reels when their UTC time arrives.';

    private function logDebug($message) {
        $logFile = WRITEPATH . 'logs/publisher_debug.log';
        $timestamp = gmdate('Y-m-d H:i:s') . ' UTC';
        file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
    }

    public function run(array $params)
    {
        chdir(ROOTPATH);
        set_time_limit(0); 
        ini_set('memory_limit', '512M'); 
        
        $db = \Config\Database::connect();
        helper(['url', 'text', 'media']);
        
        CLI::write("🚀 Auto-Publisher Checking for Scheduled Content...", 'green');

        $notifier = new NotificationHelper();
        $currentUtcTime = gmdate('Y-m-d H:i:s');
        
        // 🟢 [LOGIC 1] - PROCESS SCHEDULED VIDEOS
        $videos = $db->table('videos')
                     ->where('status', 'scheduled')
                     ->where('scheduled_at <=', $currentUtcTime)
                     ->get()->getResult();

        foreach ($videos as $v) {
            $db->transBegin();
            try {
                $db->table('videos')->where('id', $v->id)->update([
                    'status' => 'published',
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ]);
                
                $db->transCommit();
                CLI::write("✅ PUBLISHED VIDEO: ID {$v->id} at {$currentUtcTime}", 'cyan');
                $this->logDebug("Published Video ID {$v->id}");

                // 🔔 Notify Subscribers
                $notifier->notifySubscribersOnUpload($v->user_id, $v->id, 'video', $v->title, ['thumbnail' => $v->thumbnail_url]);
            } catch (\Exception $e) {
                $db->transRollback();
                $this->logDebug("DB EXCEPTION on Video {$v->id}: " . $e->getMessage());
            }
        }

        // 🟢 [LOGIC 2] - PROCESS SCHEDULED REELS
        $reels = $db->table('reels')
                    ->where('status', 'scheduled')
                    ->where('scheduled_at <=', $currentUtcTime)
                    ->get()->getResult();

        foreach ($reels as $r) {
            $db->transBegin();
            try {
                $db->table('reels')->where('id', $r->id)->update([
                    'status' => 'published',
                    'updated_at' => gmdate('Y-m-d H:i:s')
                ]);
                
                $db->transCommit();
                CLI::write("✅ PUBLISHED REEL: ID {$r->id} at {$currentUtcTime}", 'yellow');
                $this->logDebug("Published Reel ID {$r->id}");

                // 🔔 Notify Subscribers
                $notifier->notifySubscribersOnUpload($r->user_id, $r->id, 'reel', $r->caption, ['thumbnail' => $r->thumbnail_url]);
            } catch (\Exception $e) {
                $db->transRollback();
                $this->logDebug("DB EXCEPTION on Reel {$r->id}: " . $e->getMessage());
            }
        }

        CLI::write("Done checking.", 'green');
    }
}
