<?php

namespace App\Libraries;

/**
 * Member 2: Detects suspicious login patterns, brute-force attempts,
 * and sends notifications to ADMIN/SUPER_ADMIN users.
 */
class IntrusionDetector
{
    private const FAILED_THRESHOLD = 5;
    private const WINDOW_MINUTES   = 10;

    /**
     * Called after a failed login. Checks if the IP has too many recent failures
     * and creates a security notification for admins if threshold is exceeded.
     */
    public static function onLoginFailed(string $login, string $ipAddress): void
    {
        try {
            $db = \Config\Database::connect();
            $cutoff = date('Y-m-d H:i:s', time() - (self::WINDOW_MINUTES * 60));

            $failCount = $db->table('logs')
                ->where('action_type', 'LOGIN_FAILED')
                ->where('created_at >=', $cutoff)
                ->like('details', $ipAddress)
                ->countAllResults();

            if ($failCount >= self::FAILED_THRESHOLD) {
                self::alertAdmins(
                    'BRUTE_FORCE',
                    'Possible brute-force attack: ' . $failCount . ' failed logins from IP ' . $ipAddress
                    . ' in the last ' . self::WINDOW_MINUTES . ' minutes. Last attempt: "' . $login . '".'
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'IntrusionDetector::onLoginFailed: ' . $e->getMessage());
        }
    }

    /**
     * Called after account lockout.
     */
    public static function onAccountLocked(int $userId, string $username): void
    {
        self::alertAdmins(
            'ACCOUNT_LOCKED',
            'Account "' . $username . '" (ID ' . $userId . ') was locked due to too many failed login attempts.'
        );
    }

    /**
     * Send a notification to all ADMIN and SUPER_ADMIN users.
     */
    public static function alertAdmins(string $type, string $message): void
    {
        try {
            $db = \Config\Database::connect();

            $admins = $db->table('users')
                ->select('id')
                ->whereIn('role', ['ADMIN', 'SUPER_ADMIN'])
                ->where('is_active', 1)
                ->where('deleted_at IS NULL')
                ->get()
                ->getResultArray();

            $now = date('Y-m-d H:i:s');
            foreach ($admins as $admin) {
                $db->table('notifications')->insert([
                    'user_id'         => (int) $admin['id'],
                    'type'            => $type,
                    'reference_table' => 'logs',
                    'reference_id'    => null,
                    'message'         => mb_substr($message, 0, 191),
                    'is_read'         => 0,
                    'created_at'      => $now,
                ]);
            }

            AuditLogger::log('SECURITY_ALERT', null, 'logs', null, $message);
        } catch (\Throwable $e) {
            log_message('error', 'IntrusionDetector::alertAdmins: ' . $e->getMessage());
        }
    }

    /**
     * Generate an audit report summary for the Security Logs page.
     *
     * @return array<string, mixed>
     */
    public static function auditReport(int $days = 7): array
    {
        try {
            $db = \Config\Database::connect();
            $since = date('Y-m-d H:i:s', time() - ($days * 86400));

            $totalLogins = $db->table('logs')
                ->where('action_type', 'LOGIN_SUCCESS')
                ->where('created_at >=', $since)
                ->countAllResults();

            $failedLogins = $db->table('logs')
                ->where('action_type', 'LOGIN_FAILED')
                ->where('created_at >=', $since)
                ->countAllResults();

            $securityAlerts = $db->table('logs')
                ->where('action_type', 'SECURITY_ALERT')
                ->where('created_at >=', $since)
                ->countAllResults();

            $mfaEvents = $db->table('logs')
                ->whereIn('action_type', ['MFA_SUCCESS', 'MFA_FAILED'])
                ->where('created_at >=', $since)
                ->countAllResults();

            $userChanges = $db->table('logs')
                ->whereIn('action_type', ['USER_CREATED', 'USER_UPDATED', 'USER_DELETED', 'USER_RESTORED', 'ROLE_CHANGED'])
                ->where('created_at >=', $since)
                ->countAllResults();

            $mostActive = $db->table('logs')
                ->select('users.name, users.username, COUNT(*) as action_count')
                ->join('users', 'users.id = logs.user_id', 'left')
                ->where('logs.created_at >=', $since)
                ->where('logs.user_id IS NOT NULL')
                ->groupBy('logs.user_id')
                ->orderBy('action_count', 'DESC')
                ->limit(5)
                ->get()
                ->getResultArray();

            $dailyBreakdown = $db->table('logs')
                ->select("DATE(created_at) as log_date, action_type, COUNT(*) as cnt")
                ->where('created_at >=', $since)
                ->groupBy('log_date, action_type')
                ->orderBy('log_date', 'DESC')
                ->get()
                ->getResultArray();

            return [
                'days'            => $days,
                'total_logins'    => $totalLogins,
                'failed_logins'   => $failedLogins,
                'security_alerts' => $securityAlerts,
                'mfa_events'      => $mfaEvents,
                'user_changes'    => $userChanges,
                'most_active'     => $mostActive,
                'daily_breakdown' => $dailyBreakdown,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'IntrusionDetector::auditReport: ' . $e->getMessage());
            return [];
        }
    }
}
