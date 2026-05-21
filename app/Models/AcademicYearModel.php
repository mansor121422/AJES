<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicYearModel extends Model
{
    protected $table         = 'academic_years';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'label',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
        'notes',
    ];
    protected $useTimestamps = true;

    public function findActive(): ?array
    {
        return $this->where('status', 'active')->first();
    }
}
