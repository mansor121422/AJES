<?php

namespace App\Filters;

use App\Models\ApiTokenModel;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ($session->get('user_id')) {
            return null;
        }

        // Allow API clients (e.g. Android) to authenticate with Bearer token
        $header = $request->getHeaderLine('Authorization');
        $token  = '';
        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            $token = trim($m[1]);
        }

        if ($token !== '') {
            $tokenModel = new ApiTokenModel();
            $userId     = $tokenModel->getUserIdByToken($token);
            if ($userId !== null) {
                $userModel = new UserModel();
                $user      = $userModel->find($userId);
                if ($user) {
                    $session->set([
                        'user_id' => (int) $user['id'],
                        'name'    => $user['name'] ?? $user['username'] ?? 'User',
                        'role'    => $user['role'] ?? 'STUDENT',
                    ]);
                    return null;
                }
            }
        }

        // API request (JSON or Bearer expected) → 401
        if ($token !== '' || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Please log in first.',
                ]);
        }

        return redirect()->to('/')->with('error', 'Please log in first.');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}

