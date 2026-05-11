<?php

namespace App\Libraries;

/**
 * Password hashing helper — uses Argon2id when available, falls back to bcrypt.
 * Provides transparent re-hashing of older bcrypt hashes on login.
 */
class SecureHash
{
    public static function preferredAlgorithm(): string|int
    {
        if (defined('PASSWORD_ARGON2ID')) {
            return PASSWORD_ARGON2ID;
        }

        return PASSWORD_BCRYPT;
    }

    public static function make(string $password): string
    {
        return password_hash($password, self::preferredAlgorithm());
    }

    /**
     * Check whether an existing hash should be upgraded (e.g. bcrypt → Argon2id).
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::preferredAlgorithm());
    }

    public static function algorithmName(): string
    {
        $algo = self::preferredAlgorithm();

        if (defined('PASSWORD_ARGON2ID') && $algo === PASSWORD_ARGON2ID) {
            return 'Argon2id';
        }

        return 'bcrypt';
    }
}
