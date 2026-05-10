<?php

namespace App\Libraries;

/**
 * Progressive lockout after failed password checks (web + API).
 * 3rd wrong password → 1 minute; each further wrong password → 5 minutes.
 */
class LoginLockout
{
    public const FIRST_LOCK_SECONDS = 60;

    public const NEXT_LOCK_SECONDS = 300;

    public const FAILURES_BEFORE_FIRST_LOCK = 3;

    /**
     * @return positive-int|null Seconds remaining in lockout, or null if not locked.
     */
    public static function lockedRemainingSeconds(?string $lockedUntil): ?int
    {
        if ($lockedUntil === null || $lockedUntil === '') {
            return null;
        }
        $end = strtotime($lockedUntil);
        if ($end === false) {
            return null;
        }
        $rem = $end - time();

        return $rem > 0 ? $rem : null;
    }

    public static function lockoutMessage(int $secondsRemaining): string
    {
        if ($secondsRemaining >= 60) {
            $m = (int) ceil($secondsRemaining / 60);

            return 'Too many failed attempts. Try again in ' . $m . ' minute(s).';
        }

        return 'Too many failed attempts. Try again in ' . $secondsRemaining . ' second(s).';
    }

    /**
     * @return array{failed_attempts: int, last_failed_at: string, locked_until: string|null}
     */
    public static function fieldsAfterFailedPassword(int $currentAttempts): array
    {
        $newAttempts = $currentAttempts + 1;
        $until       = null;

        if ($newAttempts === self::FAILURES_BEFORE_FIRST_LOCK) {
            $until = time() + self::FIRST_LOCK_SECONDS;
        } elseif ($newAttempts > self::FAILURES_BEFORE_FIRST_LOCK) {
            $until = time() + self::NEXT_LOCK_SECONDS;
        }

        return [
            'failed_attempts' => $newAttempts,
            'last_failed_at'  => date('Y-m-d H:i:s'),
            'locked_until'    => $until !== null ? date('Y-m-d H:i:s', $until) : null,
        ];
    }
}
