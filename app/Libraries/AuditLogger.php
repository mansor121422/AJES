<?php

namespace App\Libraries;

/**
 * Writes security-relevant events to the `logs` table.
 *
 * Schema: id, user_id, action_type, related_table, related_id, details, ip_address, created_at
 */
class AuditLogger
{
    public static function log(
        string $actionType,
        ?int   $userId = null,
        ?string $relatedTable = null,
        ?int   $relatedId = null,
        ?string $details = null
    ): void {
        if ($userId === null) {
            $userId = (int) (session()->get('user_id') ?? 0) ?: null;
        }

        $ip = '';
        try {
            $ip = service('request')->getIPAddress();
        } catch (\Throwable $e) {
        }

        try {
            $db = \Config\Database::connect();
            $row = [
                'user_id'       => $userId,
                'action_type'   => $actionType,
                'related_table' => $relatedTable,
                'related_id'    => $relatedId,
                'details'       => $details,
                'created_at'    => date('Y-m-d H:i:s'),
            ];
            if ($ip !== '') {
                $row['ip_address'] = DataEncryptor::encrypt($ip);
            }
            $db->table('logs')->insert($row);
        } catch (\Throwable $e) {
            log_message('error', 'AuditLogger: ' . $e->getMessage());
        }
    }

    public static function loginSuccess(int $userId, string $username): void
    {
        self::log('LOGIN_SUCCESS', $userId, 'users', $userId, 'User "' . $username . '" logged in.');
    }

    public static function loginFailed(string $login): void
    {
        $ip = '';
        try {
            $ip = service('request')->getIPAddress();
        } catch (\Throwable $e) {
        }

        self::log('LOGIN_FAILED', null, 'users', null, 'Failed login attempt for "' . $login . '" from IP ' . $ip . '.');

        IntrusionDetector::onLoginFailed($login, $ip);
    }

    public static function logout(int $userId): void
    {
        self::log('LOGOUT', $userId, 'users', $userId);
    }

    public static function userCreated(int $newUserId, string $name, string $role): void
    {
        self::log('USER_CREATED', null, 'users', $newUserId, 'Created user "' . $name . '" with role ' . $role . '.');
    }

    public static function userUpdated(int $targetId, string $changes): void
    {
        self::log('USER_UPDATED', null, 'users', $targetId, $changes);
    }

    public static function userDeleted(int $targetId, string $name): void
    {
        self::log('USER_DELETED', null, 'users', $targetId, 'Archived user "' . $name . '".');
    }

    public static function userRestored(int $targetId, string $name): void
    {
        self::log('USER_RESTORED', null, 'users', $targetId, 'Restored user "' . $name . '".');
    }

    public static function passwordReset(int $userId): void
    {
        self::log('PASSWORD_RESET', $userId, 'users', $userId, 'Password was reset via email link.');
    }

    public static function roleChanged(int $targetId, string $oldRole, string $newRole): void
    {
        self::log('ROLE_CHANGED', null, 'users', $targetId, 'Role changed from ' . $oldRole . ' to ' . $newRole . '.');
    }

    /**
     * Fetch recent audit-log entries for the Security Logs UI.
     *
     * @return list<array<string, mixed>>
     */
    public static function recent(int $limit = 50): array
    {
        return self::paginated($limit, 0);
    }

    public static function count(): int
    {
        try {
            $db = \Config\Database::connect();
            return (int) $db->table('logs')->countAllResults();
        } catch (\Throwable $e) {
            log_message('error', 'AuditLogger::count: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function paginated(int $perPage = 10, int $offset = 0): array
    {
        try {
            $db = \Config\Database::connect();
            $rows = $db->table('logs')
                ->select('logs.*, users.name as user_name, users.username')
                ->join('users', 'users.id = logs.user_id', 'left')
                ->orderBy('logs.created_at', 'DESC')
                ->limit($perPage, $offset)
                ->get()
                ->getResultArray();

            foreach ($rows as &$r) {
                if (! empty($r['ip_address'])) {
                    $r['ip_address'] = DataEncryptor::decrypt($r['ip_address']);
                }
            }
            unset($r);

            return $rows;
        } catch (\Throwable $e) {
            log_message('error', 'AuditLogger::paginated: ' . $e->getMessage());
            return [];
        }
    }
}
