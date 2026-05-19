<?php

namespace App\Models;

use App\Libraries\DataEncryptor;
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
        'student_id',
        'birthdate',
        'age',
        'address',
        'guardian_name',
        'guardian_contact',
        'email',
        'username',
        'password_hash',
        'password_history',
        'role',
        'section_id',
        'guidance_flag',
        'is_active',
        'is_online',
        'last_seen_at',
        'failed_attempts',
        'last_failed_at',
        'locked_until',
        'contact_number',
        'bio',
        'profile_photo',
        'admin_privileges',
        'mfa_enabled',
        'mfa_code',
        'mfa_expires_at',
        'deleted_at',
    ];

    protected $beforeInsert = ['encryptSensitive'];
    protected $beforeUpdate = ['encryptSensitive'];
    protected $afterFind    = ['decryptSensitive'];

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

    // ------------------------------------------------------------------
    // Model-event callbacks for transparent column-level encryption
    // ------------------------------------------------------------------

    protected function encryptSensitive(array $eventData): array
    {
        if (isset($eventData['data']) && is_array($eventData['data'])) {
            $eventData['data'] = DataEncryptor::encryptFields(
                $eventData['data'],
                DataEncryptor::sensitiveUserFields()
            );
        }

        return $eventData;
    }

    protected function decryptSensitive(array $eventData): array
    {
        $fields = DataEncryptor::sensitiveUserFields();

        if (isset($eventData['data']) && is_array($eventData['data'])) {
            if (isset($eventData['data'][0]) && is_array($eventData['data'][0])) {
                foreach ($eventData['data'] as &$row) {
                    $row = DataEncryptor::decryptUserRowForDisplay($row);
                }
                unset($row);
            } else {
                $eventData['data'] = DataEncryptor::decryptUserRowForDisplay($eventData['data']);
            }
        }

        return $eventData;
    }
}

