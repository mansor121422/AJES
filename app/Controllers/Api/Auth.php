<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ApiTokenModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * REST API authentication for mobile (Android) and other API clients.
 * POST /api/login accepts username/email + password and returns a token.
 */
class Auth extends BaseController
{
    protected UserModel $users;
    protected ApiTokenModel $tokens;

    public function __construct()
    {
        $this->users  = new UserModel();
        $this->tokens = new ApiTokenModel();
        helper(['url']);
    }

    /**
     * POST /api/login
     * Body (JSON or form): username (or email), password
     * Success: { "status": "success", "data": { "user_id", "username", "name", "role", "token" } }
     * Failure: { "status": "error", "message": "..." }
     */
    public function login(): ResponseInterface
    {
        $request = $this->request;

        // Accept JSON or form body
        $json = $request->getJSON(true);
        if (is_array($json)) {
            $username = trim((string) ($json['username'] ?? $json['email'] ?? ''));
            $password = (string) ($json['password'] ?? '');
        } else {
            $username = trim((string) ($request->getPost('username') ?? $request->getPost('email') ?? ''));
            $password = (string) ($request->getPost('password') ?? '');
        }

        if ($username === '' || $password === '') {
            return $this->failResponse('Username and password are required.', 400);
        }

        $user = $this->users->findByLogin($username);

        if (! $user) {
            return $this->failResponse('Invalid credentials.', 401);
        }

        if (($user['failed_attempts'] ?? 0) >= 5) {
            return $this->failResponse('Account locked. Please contact administrator.', 403);
        }

        if (! password_verify($password, $user['password_hash'])) {
            $this->users->update($user['id'], [
                'failed_attempts' => ($user['failed_attempts'] ?? 0) + 1,
                'last_failed_at'  => date('Y-m-d H:i:s'),
            ]);
            return $this->failResponse('Invalid credentials.', 401);
        }

        $this->users->update($user['id'], [
            'failed_attempts' => 0,
            'last_failed_at'  => null,
        ]);

        $token = $this->tokens->createToken((int) $user['id'], 30);

        $data = [
            'user_id'  => (int) $user['id'],
            'username' => $user['username'] ?? $user['email'] ?? '',
            'name'     => $user['name'] ?? $user['username'] ?? 'User',
            'role'     => $user['role'] ?? 'STUDENT',
            'token'    => $token,
        ];

        return $this->successResponse($data);
    }

    /**
     * POST /api/logout (optional – revoke token so it cannot be reused).
     * Header: Authorization: Bearer <token>
     */
    public function logout(): ResponseInterface
    {
        $token = $this->getBearerToken();
        if ($token !== '') {
            $this->tokens->revokeToken($token);
        }
        return $this->successResponse(['message' => 'Logged out.'], 200);
    }

    private function getBearerToken(): string
    {
        $header = $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function successResponse(array $data, int $code = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($code)
            ->setJSON([
                'status' => 'success',
                'data'   => $data,
            ]);
    }

    private function failResponse(string $message, int $code = 400): ResponseInterface
    {
        return $this->response
            ->setStatusCode($code)
            ->setJSON([
                'status'  => 'error',
                'message' => $message,
            ]);
    }
}
