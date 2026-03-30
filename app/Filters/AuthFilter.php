<?php

namespace App\Filters;

use App\Models\ApiTokenModel;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Presence columns may not exist yet if migrations weren't run.
     * We detect them once per request and safely skip presence updates.
     */
    private function usersHasPresenceColumns(): bool
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        $db = \Config\Database::connect();
        $hasIsOnline = $db->query("SHOW COLUMNS FROM `users` LIKE 'is_online'")->getNumRows() > 0;
        $hasLastSeen = $db->query("SHOW COLUMNS FROM `users` LIKE 'last_seen_at'")->getNumRows() > 0;
        $checked = $hasIsOnline && $hasLastSeen;
        return $checked;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if ($session->get('user_id')) {
            $this->touchPresence((int) $session->get('user_id'));
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
                    $this->touchPresence((int) $user['id']);
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

    /**
     * Keep `users.is_online` / `users.last_seen_at` fresh while the session
     * is active.
     */
    private function touchPresence(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        if (! $this->usersHasPresenceColumns()) {
            return;
        }

        $heartbeatSeconds = (int) (config('App')->presenceHeartbeatSeconds ?? 60);
        $heartbeatSeconds = max(1, $heartbeatSeconds);

        $now       = date('Y-m-d H:i:s');
        $threshold = date('Y-m-d H:i:s', time() - $heartbeatSeconds);

        // Best-effort: avoid excessive DB writes by only updating when
        // `last_seen_at` is NULL or older than the heartbeat threshold.
        // Use a bound query to guarantee the datetime is quoted correctly.
        $db = \Config\Database::connect();
        $sql = "UPDATE `users`
                SET `is_online` = ?, `last_seen_at` = ?
                WHERE `id` = ?
                  AND `is_online` = 1
                  AND (`last_seen_at` IS NULL OR `last_seen_at` < ?)";
        try {
            $db->query($sql, [1, $now, $userId, $threshold]);
        } catch (\Throwable $e) {
            // Presence should never break authenticated pages.
            log_message('error', 'Presence update failed for user_id=' . $userId . ': ' . $e->getMessage());
        }
    }
}

