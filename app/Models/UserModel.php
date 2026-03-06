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
        'surname',
        'first_name',
        'middle_initial',
        'name_suffix',
        'gender',
        'grade_level',
        'email',
        'username',
        'password_hash',
        'role',
        'section_id',
        'guidance_flag',
        'is_active',
        'failed_attempts',
        'last_failed_at',
        'deleted_at',
    ];

    /** Full name: Surname, First name MI Suffix (or name if not set). */
    public static function fullName(array $user): string
    {
        $s = trim($user['surname'] ?? '');
        $f = trim($user['first_name'] ?? '');
        $m = trim($user['middle_initial'] ?? '');
        $x = trim($user['name_suffix'] ?? '');
        if ($s !== '' || $f !== '') {
            return $s . ($f !== '' ? ', ' . $f : '') . ($m !== '' ? ' ' . $m : '') . ($x !== '' ? ' ' . $x : '');
        }
        return $user['name'] ?? $user['username'] ?? '—';
    }

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

