<?php

namespace App\Models;

use CodeIgniter\Model;

class ApiTokenModel extends Model
{
    protected $table         = 'api_tokens';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;
    protected $returnType   = 'array';
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';

    protected $allowedFields = [
        'user_id',
        'token',
        'expires_at',
        'created_at',
    ];

    /**
     * Create a new token for user. Returns the plain token string (store this in DB hashed or as-is).
     * Expires in 30 days by default.
     */
    public function createToken(int $userId, int $ttlDays = 30): string
    {
        $token    = bin2hex(random_bytes(32));
        $expires  = date('Y-m-d H:i:s', time() + ($ttlDays * 86400));
        $created  = date('Y-m-d H:i:s');

        $this->insert([
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => $expires,
            'created_at' => $created,
        ]);

        return $token;
    }

    /**
     * Find user_id by token. Returns null if token invalid or expired.
     */
    public function getUserIdByToken(string $token): ?int
    {
        $row = $this->where('token', $token)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();

        return $row ? (int) $row['user_id'] : null;
    }

    /**
     * Revoke a token (e.g. on logout).
     */
    public function revokeToken(string $token): bool
    {
        return $this->where('token', $token)->delete() !== false;
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAllForUser(int $userId): bool
    {
        return $this->where('user_id', $userId)->delete() !== false;
    }
}
