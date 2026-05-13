<?php

namespace App\Libraries;

/**
 * Member 1: Tracks active user sessions — start, heartbeat, end.
 */
class SessionTracker
{
    public static function start(int $userId): void
    {
        $request   = service('request');
        $sessionId = session_id() ?: session()->get('__ci_last_regenerate') ?? bin2hex(random_bytes(16));
        $ip  = DataEncryptor::encrypt($request->getIPAddress());
        $ua  = $request->getUserAgent()->getAgentString();
        $now = date('Y-m-d H:i:s');

        try {
            $db = \Config\Database::connect();

            $existing = $db->table('user_sessions')
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->where('is_active', 1)
                ->get()->getRowArray();

            if ($existing) {
                $db->table('user_sessions')
                    ->where('id', $existing['id'])
                    ->update(['last_activity' => $now, 'ip_address' => $ip]);
            } else {
                $db->table('user_sessions')->insert([
                    'user_id'       => $userId,
                    'session_id'    => $sessionId,
                    'ip_address'    => $ip,
                    'user_agent'    => $ua,
                    'started_at'    => $now,
                    'last_activity' => $now,
                    'is_active'     => 1,
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'SessionTracker::start: ' . $e->getMessage());
        }
    }

    public static function heartbeat(int $userId): void
    {
        $sessionId = session_id() ?: '';
        if ($sessionId === '') {
            return;
        }

        try {
            $db = \Config\Database::connect();
            $db->table('user_sessions')
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->where('is_active', 1)
                ->update(['last_activity' => date('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            log_message('error', 'SessionTracker::heartbeat: ' . $e->getMessage());
        }
    }

    public static function end(int $userId): void
    {
        try {
            $db = \Config\Database::connect();
            $db->table('user_sessions')
                ->where('user_id', $userId)
                ->where('is_active', 1)
                ->update(['is_active' => 0]);
        } catch (\Throwable $e) {
            log_message('error', 'SessionTracker::end: ' . $e->getMessage());
        }
    }

    /**
     * Mark stale sessions (no heartbeat for >10 min) as inactive.
     */
    public static function cleanupStale(int $timeoutMinutes = 10): void
    {
        try {
            $cutoff = date('Y-m-d H:i:s', time() - ($timeoutMinutes * 60));
            $db = \Config\Database::connect();
            $db->table('user_sessions')
                ->where('is_active', 1)
                ->where('last_activity <', $cutoff)
                ->update(['is_active' => 0]);
        } catch (\Throwable $e) {
            log_message('error', 'SessionTracker::cleanupStale: ' . $e->getMessage());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function activeSessions(): array
    {
        self::cleanupStale();

        try {
            $db = \Config\Database::connect();
            $rows = $db->table('user_sessions')
                ->select('user_sessions.*, users.name as user_name, users.username, users.role')
                ->join('users', 'users.id = user_sessions.user_id', 'left')
                ->where('user_sessions.is_active', 1)
                ->orderBy('user_sessions.last_activity', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($rows as &$r) {
                $r['ip_address'] = DataEncryptor::decrypt($r['ip_address'] ?? '');
            }
            unset($r);

            return $rows;
        } catch (\Throwable $e) {
            log_message('error', 'SessionTracker::activeSessions: ' . $e->getMessage());
            return [];
        }
    }
}
