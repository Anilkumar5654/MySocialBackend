<?php
namespace App\Controllers\Api\Settings;

use App\Controllers\BaseController;
use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;

class SecurityController extends BaseController {
    use ResponseTrait;
    protected $userModel;

    public function __construct() {
        $this->userModel = new UserModel();
    }

    // POST /api/settings/security/change-password
    public function changePassword() {
        $userId = $this->request->getHeaderLine('User-ID');
        $input = $this->request->getJSON(true) ?: $this->request->getVar();
        
        $current = $input['current_password'] ?? null;
        $new = $input['new_password'] ?? null;

        $user = $this->userModel->find($userId);
        if (!$user || !password_verify($current, $user['password'])) {
            return $this->fail('Current password does not match', 401);
        }

        $this->userModel->update($userId, [
            'password' => password_hash($new, PASSWORD_DEFAULT)
        ]);

        return $this->respond(['message' => 'Password changed successfully']);
    }
}
