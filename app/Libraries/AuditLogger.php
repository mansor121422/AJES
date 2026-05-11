<?php

namespace App\Libraries;

/**
 * Writes security-relevant events to the `logs` table.
 *
 * Schema (from CreateCoreTables migration):
 *   id, user_id, action_type, related_table, related_id, details, created_at
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

        try {
            $db = \Config\Database::connect();
            $db->table('logs')->insert([
                'user_id'       => $userId,
                'action_type'   => $actionType,
                'related_table' => $relatedTable,
                'related_id'    => $relatedId,
                'details'       => $details,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
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
        self::log('LOGIN_FAILED', null, 'users', null, 'Failed login attempt for "' . $login . '".');
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
        try {
            $db = \Config\Database::connect();
            return $db->table('logs')
                ->select('logs.*, users.name as user_name, users.username')
                ->join('users', 'users.id = logs.user_id', 'left')
                ->orderBy('logs.created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'AuditLogger::recent: ' . $e->getMessage());
            return [];
        }
    }
}
