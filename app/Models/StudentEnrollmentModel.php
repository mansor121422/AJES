<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentEnrollmentModel extends Model
{
    protected $table         = 'student_enrollments';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'user_id',
        'academic_year_id',
        'grade_level',
        'section_id',
        'section_name_snapshot',
        'subjects_snapshot',
        'outcome',
        'is_current',
    ];
    protected $useTimestamps = true;

    public function findCurrentForStudent(int $userId): ?array
    {
        return $this->where('user_id', $userId)->where('is_current', 1)->first();
    }
}
