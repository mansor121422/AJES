<?php

namespace App\Libraries;

/**
 * AES-256-CBC field-level encryption for sensitive user data.
 * Key is derived from the CI4 encryption key in .env.
 */
class DataEncryptor
{
    private const CIPHER = 'aes-256-cbc';

    private static function getKey(): string
    {
        $key = env('ENCRYPTION_KEY', '');
        if ($key === '' || $key === false) {
            $key = env('encryption.key', '');
        }
        if ($key === '' || $key === false) {
            $key = config('Encryption')->key ?? '';
        }
        if (is_string($key)) {
            if (preg_match('/hex2bin:([0-9a-fA-F]+)/', $key, $m)) {
                $key = hex2bin($m[1]);
            } elseif (preg_match('/base64:(.+)/', $key, $m)) {
                $key = base64_decode($m[1]);
            }
        }
        if (! is_string($key) || strlen($key) < 16) {
            throw new \RuntimeException('Encryption key not configured. Set ENCRYPTION_KEY in .env (min 16 bytes).');
        }

        return hash('sha256', $key, true);
    }

    public static function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }

        $key = self::getKey();
        $iv  = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $encrypted = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed.');
        }

        return 'ENC:' . base64_encode($iv . $encrypted);
    }

    public static function decrypt(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return $ciphertext;
        }

        if (! str_starts_with($ciphertext, 'ENC:')) {
            return $ciphertext;
        }

        $key  = self::getKey();
        $data = base64_decode(substr($ciphertext, 4));
        $ivLen = openssl_cipher_iv_length(self::CIPHER);
        $iv   = substr($data, 0, $ivLen);
        $raw  = substr($data, $ivLen);

        $decrypted = openssl_decrypt($raw, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            return $ciphertext;
        }

        return $decrypted;
    }

    /**
     * Encrypt specific fields in an associative array.
     *
     * @param array<string, mixed> $data
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    public static function encryptFields(array $data, array $fields): array
    {
        foreach ($fields as $f) {
            if (isset($data[$f]) && is_string($data[$f]) && $data[$f] !== '') {
                $data[$f] = self::encrypt($data[$f]);
            }
        }

        return $data;
    }

    /**
     * Decrypt specific fields in an associative array (user row).
     *
     * @param array<string, mixed> $data
     * @param list<string> $fields
     * @return array<string, mixed>
     */
    public static function decryptFields(array $data, array $fields): array
    {
        foreach ($fields as $f) {
            if (isset($data[$f]) && is_string($data[$f]) && $data[$f] !== '') {
                $data[$f] = self::decrypt($data[$f]);
            }
        }

        return $data;
    }

    /**
     * Fields encrypted at rest. Email is excluded because it is used
     * for login lookups and uniqueness constraints.
     */
    public static function sensitiveUserFields(): array
    {
        return ['guardian_name', 'guardian_contact', 'contact_number', 'address'];
    }
}
