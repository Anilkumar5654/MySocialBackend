<?php
namespace App\Controllers\Api\Settings;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class AccountController extends BaseController {
    use ResponseTrait;
    protected $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    // POST /api/settings/delete_account
    public function deleteAccount() {
        $userId = $this->request->getHeaderLine('User-ID');
        $input = $this->request->getJSON(true) ?: $this->request->getVar();
        $password = $input['password'] ?? null;

        $user = $this->userModel->find($userId);
        if (!$user || !password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Incorrect password. Deletion failed.');
        }

        // Soft Delete (30 days window)
        $this->userModel->update($userId, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        return $this->respond(['message' => 'Account scheduled for deletion.']);
    }
}
