<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AdSettlementWorker extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'ads:settle';
    protected $description = 'Settles ad revenue with global revenue share and original creator payouts';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        CLI::write("🚀 Starting Advanced Revenue Settlement Process...", 'yellow');

        // 1. Global Settings Fetch Karein
        $globalSettings = $db->table('ad_settings')
                             ->whereIn('setting_key', ['revenue_share_original_creator', 'revenue_share_uploader'])
                             ->get()->getResultArray();
        
        $settings = array_column($globalSettings, 'setting_value', 'setting_key');
        $originalPct = (float)($settings['revenue_share_original_creator'] ?? 70);
        $uploaderPct = (float)($settings['revenue_share_uploader'] ?? 30);

        $tables = ['ad_clicks', 'ad_views'];

        foreach ($tables as $table) {
            CLI::write("-------------------------------------------", 'yellow');
            CLI::write("Checking table: $table", 'cyan');

            // 2. Query with JOIN: Copyright status aur Revenue Share info ek saath
            $builder = $db->table($table . ' as ads');
            $builder->select('ads.*, v.copyright_status, rs.id as share_entry_id, rs.original_creator_id');
            $builder->join('videos v', 'v.id = ads.content_id', 'left');
            $builder->join('revenue_shares rs', 'rs.claimed_content_id = ads.content_id AND rs.status = "ACTIVE"', 'left');
            $builder->where('ads.is_settled', 0);
            
            $pending = $builder->limit(500)->get()->getResult();

            if (empty($pending)) {
                CLI::write("No pending settlements in $table.", 'green');
                continue;
            }

            foreach ($pending as $row) {
                $db->transStart();

                // 🚀 CASE 1: Agar Creator Revenue 0 hai (Platform ka 100% Profit)
                if ((float)$row->creator_revenue <= 0) {
                    $db->table($table)->where('id', $row->id)->update([
                        'is_settled' => 1,
                        'settled_at' => date('Y-m-d H:i:s')
                    ]);
                    CLI::write("💎 Platform Profit Captured: ID {$row->id}", 'blue');
                    $db->transComplete();
                    continue;
                }

                // 🚀 CASE 2: Revenue Share Logic (Agar Video CLAIMED hai)
                if ($row->copyright_status === 'CLAIMED' && !empty($row->original_creator_id)) {
                    
                    $totalRevenue = (float)$row->creator_revenue;
                    $originalCut = ($totalRevenue * $originalPct) / 100;
                    $uploaderCut = ($totalRevenue * $uploaderPct) / 100;

                    // A. Entry for Original Creator
                    $this->processPayout($row->original_creator_id, $originalCut, 'ad_revenue_claim_share', $row, $row->share_entry_id);
                    
                    // B. Entry for Uploader
                    $this->processPayout($row->creator_id, $uploaderCut, 'ad_revenue_uploader_share', $row, $row->share_entry_id);

                    CLI::write("💰 Shared ID {$row->id}: Org({$originalCut}) | Upl({$uploaderCut})", 'yellow');

                } else {
                    // 🚀 CASE 3: Normal Settlement (No Copyright)
                    $this->processPayout($row->creator_id, $row->creator_revenue, ($table === 'ad_clicks' ? 'ad_click_revenue' : 'ad_view_revenue'), $row);
                    CLI::write("✅ Normal ID {$row->id}: {$row->creator_revenue} USD to User {$row->creator_id}", 'green');
                }

                // Mark ad record as settled
                $db->table($table)->where('id', $row->id)->update([
                    'is_settled' => 1,
                    'settled_at' => date('Y-m-d H:i:s')
                ]);

                $db->transComplete();
            }
        }

        CLI::write("-------------------------------------------", 'yellow');
        CLI::write("🏁 Settlement Complete!", 'white', 'blue');
    }

    /**
     * Helper to handle Earnings Entry and Wallet Update
     */
    private function processPayout($userId, $amount, $type, $row, $sourceId = null) {
        $db = \Config\Database::connect();

        // 1. Creator Earnings Entry
        $db->table('creator_earnings')->insert([
            'user_id'      => $userId,
            'earning_type' => $type,
            'amount'       => $amount,
            'currency'     => 'USD',
            'content_type' => $row->content_type,
            'content_id'   => $row->content_id,
            'source_id'    => $sourceId,
            'status'       => 'approved',
            'is_settled'   => 1,
            'settled_at'   => date('Y-m-d H:i:s'),
            'created_at'   => date('Y-m-d H:i:s')
        ]);

        // 2. Wallet Balance Update
        $db->query("UPDATE creator_wallets SET balance = balance + ?, updated_at = NOW() WHERE user_id = ?", [$amount, $userId]);
    }
}
