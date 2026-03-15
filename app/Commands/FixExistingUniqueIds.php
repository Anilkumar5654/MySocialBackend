<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixExistingUniqueIds extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'ids:fix';
    protected $description = 'Generate Smart Unique IDs for existing records with NULL unique_id.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        // Hamara naya helper call kar rahe hain jo USR, REL, VID generate karta hai
        // Note: IdGeneratorHelper global load hona chahiye ya yahan instantiate karein
        $idGenerator = new \App\Helpers\IdGeneratorHelper();

        CLI::write("🚀 Starting Smart ID Migration for MySocial...", 'green');

        // Configuration: Table Name => Entity Type (Helper Prefix ke liye)
        $tables = [
            'users'    => 'user',
            'channels' => 'channel',
            'videos'   => 'video',
            'reels'    => 'reel',
            'posts'    => 'post'
        ];

        foreach ($tables as $tableName => $type) {
            CLI::write("\n-------------------------------------------", 'yellow');
            CLI::write("🔍 Scanning Table: $tableName", 'yellow');

            if (!$db->tableExists($tableName)) {
                CLI::error("   -> Table '$tableName' not found. Skipping.");
                continue;
            }

            // Sirf wahi records jinme unique_id null ya empty hai
            $builder = $db->table($tableName);
            $builder->where("(unique_id IS NULL OR unique_id = '')");
            
            $records = $builder->get()->getResultArray();
            $count = count($records);

            if ($count === 0) {
                CLI::write("   -> All records already have Unique IDs.", 'cyan');
                continue;
            }

            CLI::write("   -> Found $count records to fix. Processing...", 'cyan');

            $successCount = 0;

            foreach ($records as $row) {
                $id = $row['id'];
                
                /**
                 * Parent Serial Selection Logic:
                 * User ke liye uska numeric ID
                 * Baki content ke liye uska user_id serial link hai
                 */
                $parentSerial = ($tableName === 'users') ? $id : ($row['user_id'] ?? $id);

                // Generate Smart ID using the helper pattern
                $smartId = $idGenerator->generate($type, $parentSerial);

                // Duplicate safety check (Command line par bhi double check zaroori hai)
                while ($db->table($tableName)->where('unique_id', $smartId)->countAllResults() > 0) {
                    $smartId = $idGenerator->generate($type, $parentSerial);
                }

                // Update Database
                $db->table($tableName)->where('id', $id)->update([
                    'unique_id' => $smartId
                ]);

                $successCount++;
                if ($successCount % 10 == 0) {
                    CLI::write("      Progress: $successCount/$count processed...");
                }
            }

            CLI::write("   -> Finished $tableName. Total Fixed: $successCount", 'green');
        }

        CLI::write("\n✅ Database Unique IDs are now synchronized!", 'green');
    }
}
