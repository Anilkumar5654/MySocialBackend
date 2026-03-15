<?php

namespace App\Controllers\Api\Creator;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class PayoutSettingsController extends BaseController {
    use ResponseTrait;

    public function index() {
        $userId = $this->request->getHeaderLine('User-ID');
        $db = \Config\Database::connect();
        
        $settings = $db->table('user_payout_settings')
                       ->where('user_id', $userId)
                       ->get()
                       ->getRowArray();
        
        return $this->respond([
            'success' => true,
            'data' => $settings ?: [
                'payment_method' => 'bank_transfer',
                'upi_id' => '',
                'bank_name' => '',
                'account_number' => '',
                'account_holder_name' => '',
                'routing_number' => '' 
            ]
        ]);
    }

    public function save() {
        $userId = $this->request->getHeaderLine('User-ID');
        $db = \Config\Database::connect();
        $input = $this->request->getJSON(true) ?? $this->request->getPost();

        if (!$userId) return $this->failUnauthorized();

        $data = [
            'user_id'             => $userId,
            'payment_method'      => $input['payment_method'] ?? 'bank_transfer',
            'bank_name'           => $input['bank_name'] ?? null,
            'account_holder_name' => $input['account_holder_name'] ?? null,
            'account_number'      => $input['account_number'] ?? null,
            'routing_number'      => $input['routing_number'] ?? null,
            'upi_id'              => $input['upi_id'] ?? null,
            'updated_at'          => date('Y-m-d H:i:s')
        ];

        $db->transStart();

        $exists = $db->table('user_payout_settings')->where('user_id', $userId)->countAllResults();

        if ($exists) {
            $db->table('user_payout_settings')->where('user_id', $userId)->update($data);
        } else {
            $db->table('user_payout_settings')->insert($data);
        }

        // ✅ IMPORTANT FIX: Update Payout Setup Flag in Users Table
        // Isse Dashboard aur Rewards screen ko pata chalega ki setup complete hai
        $db->table('users')->where('id', $userId)->update(['is_payout_setup' => 1]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->failServerError('Failed to save settings.');
        }

        return $this->respond(['success' => true, 'message' => 'Payout settings updated successfully!']);
    }
}
