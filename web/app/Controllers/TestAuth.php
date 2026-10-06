<?php
namespace App\Controllers;
use CodeIgniter\Controller;

class TestAuth extends Controller {
    public function ping() {
        return $this->response->setJSON(['status' => 'success', 'message' => 'Test Ping Successful']);
    }
}
