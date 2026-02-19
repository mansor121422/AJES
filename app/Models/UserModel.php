<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $useAutoIncrement = true;

    protected $returnType    = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'name',
        'email',
        'username',
        'password_hash',
        'role',
        'section_id',
        'guidance_flag',
        'is_active',
        'failed_attempts',
        'last_failed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function findByLogin(string $login): ?array
    {
        return $this->groupStart()
                ->where('email', $login)
                ->orWhere('username', $login)
            ->groupEnd()
            ->where('is_active', 1)
            ->first() ?: null;
    }
}

