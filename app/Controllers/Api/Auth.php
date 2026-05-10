<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\LoginLockout;
use App\Models\ApiTokenModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\Exceptions\HTTPException;
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

        // Accept JSON or form body; malformed JSON should not crash the endpoint.
        $json = null;
        try {
            $json = $request->getJSON(true);
        } catch (HTTPException) {
            $json = null;
        }
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

        $lockedFor = LoginLockout::lockedRemainingSeconds($user['locked_until'] ?? null);
        if ($lockedFor !== null) {
            return $this->failResponse(
                LoginLockout::lockoutMessage($lockedFor),
                429,
                ['retry_after_seconds' => $lockedFor]
            );
        }

        if (! password_verify($password, $user['password_hash'])) {
            $patch = LoginLockout::fieldsAfterFailedPassword((int) ($user['failed_attempts'] ?? 0));
            $this->users->update($user['id'], $patch);

            if ($patch['locked_until'] !== null) {
                $sec = LoginLockout::lockedRemainingSeconds($patch['locked_until']);

                return $this->failResponse(
                    $sec !== null ? LoginLockout::lockoutMessage($sec) : 'Invalid credentials.',
                    429,
                    $sec !== null ? ['retry_after_seconds' => $sec] : []
                );
            }

            return $this->failResponse('Invalid credentials.', 401);
        }

        $this->users->update($user['id'], [
            'failed_attempts' => 0,
            'last_failed_at'  => null,
            'locked_until'    => null,
        ]);

        $token = $this->tokens->createToken((int) $user['id'], 30);

        // Presence: mark user as online when API login succeeds.
        try {
            $this->users->update((int) $user['id'], [
                'is_online'    => 1,
                'last_seen_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Presence update failed on api login for user_id=' . (int) $user['id'] . ': ' . $e->getMessage());
        }

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

        // Presence: mark user offline (best-effort) before revoking the token.
        $userId = $token !== '' ? $this->tokens->getUserIdByToken($token) : null;

        if ($token !== '') {
            $this->tokens->revokeToken($token);
        }

        if ($userId !== null) {
            try {
                $this->users->update($userId, [
                    'is_online'    => 0,
                    'last_seen_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'Presence update failed on api logout for user_id=' . (int) $userId . ': ' . $e->getMessage());
            }
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

    private function failResponse(string $message, int $code = 400, array $extra = []): ResponseInterface
    {
        return $this->response
            ->setStatusCode($code)
            ->setJSON(array_merge([
                'status'  => 'error',
                'message' => $message,
            ], $extra));
    }
}
