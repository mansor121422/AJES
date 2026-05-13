<?php

namespace App\Libraries;

/**
 * Member 1: Records all user activities to the `activity_logs` table.
 * Sensitive fields (IP, details) are encrypted at rest via DataEncryptor.
 */
class ActivityLogger
{
    public static function log(
        string  $action,
        ?string $module = null,
        ?string $details = null,
        ?int    $userId = null
    ): void {
        if ($userId === null) {
            $userId = (int) (session()->get('user_id') ?? 0) ?: null;
        }

        $request = service('request');

        $ip = $request->getIPAddress();
        $ua = $request->getUserAgent()->getAgentString();

        $encIp = DataEncryptor::encrypt($ip);
        $encDetails = ($details !== null && $details !== '') ? DataEncryptor::encrypt($details) : null;

        try {
            $db = \Config\Database::connect();
            $db->table('activity_logs')->insert([
                'user_id'    => $userId,
                'action'     => $action,
                'module'     => $module,
                'url'        => (string) $request->getUri(),
                'method'     => $request->getMethod(),
                'ip_address' => $encIp,
                'user_agent' => $ua,
                'details'    => $encDetails,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'ActivityLogger: ' . $e->getMessage());
        }
    }

    public static function pageVisit(?int $userId = null): void
    {
        self::log('PAGE_VISIT', null, null, $userId);
    }

    /**
     * Fetch recent activity log entries (decrypting sensitive fields).
     *
     * @return list<array<string, mixed>>
     */
    public static function recent(int $limit = 50): array
    {
        try {
            $db = \Config\Database::connect();
            $rows = $db->table('activity_logs')
                ->select('activity_logs.*, users.name as user_name, users.username')
                ->join('users', 'users.id = activity_logs.user_id', 'left')
                ->orderBy('activity_logs.created_at', 'DESC')
                ->limit($limit)
                ->get()
                ->getResultArray();

            foreach ($rows as &$r) {
                $r['ip_address'] = DataEncryptor::decrypt($r['ip_address'] ?? '');
                $r['details']    = DataEncryptor::decrypt($r['details'] ?? '');
            }
            unset($r);

            return $rows;
        } catch (\Throwable $e) {
            log_message('error', 'ActivityLogger::recent: ' . $e->getMessage());
            return [];
        }
    }
}
