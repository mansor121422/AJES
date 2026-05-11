<?php

namespace App\Libraries;

/**
 * Lightweight HMAC-SHA256 JWT for stateless API authentication.
 * No external dependencies — pure PHP implementation.
 */
class JwtAuth
{
    private static function getSecret(): string
    {
        $key = env('JWT_SECRET', env('encryption.key', ''));
        if ($key === '' || $key === false) {
            $key = config('Encryption')->key ?? '';
        }
        if (is_string($key) && str_starts_with($key, 'hex2bin:')) {
            $key = hex2bin(substr($key, 7));
        }
        if (is_string($key) && str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        if (! is_string($key) || strlen($key) < 16) {
            throw new \RuntimeException('JWT secret not configured. Set JWT_SECRET or encryption.key in .env.');
        }

        return $key;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Issue a JWT for the given user.
     *
     * @param array<string, mixed> $user  User row (must contain id, username, role)
     * @param int $ttlSeconds             Token lifetime (default 8 hours)
     */
    public static function encode(array $user, int $ttlSeconds = 28800): string
    {
        $now = time();

        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $payload = self::base64UrlEncode(json_encode([
            'sub'      => (int) $user['id'],
            'username' => $user['username'] ?? '',
            'role'     => $user['role'] ?? '',
            'iat'      => $now,
            'exp'      => $now + $ttlSeconds,
        ], JSON_THROW_ON_ERROR));

        $signature = self::base64UrlEncode(
            hash_hmac('sha256', $header . '.' . $payload, self::getSecret(), true)
        );

        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Decode and validate a JWT.
     *
     * @return array<string, mixed>|null  Payload array on success, null on failure.
     */
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $expectedSig = self::base64UrlEncode(
            hash_hmac('sha256', $header . '.' . $payload, self::getSecret(), true)
        );

        if (! hash_equals($expectedSig, $signature)) {
            return null;
        }

        $data = json_decode(self::base64UrlDecode($payload), true);
        if (! is_array($data)) {
            return null;
        }

        if (isset($data['exp']) && $data['exp'] < time()) {
            return null;
        }

        return $data;
    }
}
