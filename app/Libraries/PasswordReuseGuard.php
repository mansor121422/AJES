<?php

namespace App\Libraries;

/**
 * Reject new passwords that match the current hash or any of the last N retired hashes.
 */
class PasswordReuseGuard
{
    public const HISTORY_MAX = 10;

    /**
     * @return list<string>
     */
    public static function historyFromDb(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $h) {
            if (is_string($h) && $h !== '') {
                $out[] = $h;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $historyHashes Retired password hashes (oldest last).
     */
    public static function isPasswordReused(string $plain, string $currentHash, array $historyHashes): bool
    {
        if ($currentHash !== '' && password_verify($plain, $currentHash)) {
            return true;
        }
        foreach ($historyHashes as $h) {
            if (password_verify($plain, $h)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $previousHistory
     */
    public static function appendPreviousHash(string $oldCurrentHash, array $previousHistory): ?string
    {
        if ($oldCurrentHash === '') {
            return null;
        }
        $merged = array_merge([$oldCurrentHash], $previousHistory);
        $unique = [];
        foreach ($merged as $h) {
            if (! is_string($h) || $h === '') {
                continue;
            }
            if (! in_array($h, $unique, true)) {
                $unique[] = $h;
            }
        }
        $trimmed = array_slice($unique, 0, self::HISTORY_MAX);
        if ($trimmed === []) {
            return null;
        }

        return json_encode($trimmed, JSON_UNESCAPED_SLASHES);
    }
}
