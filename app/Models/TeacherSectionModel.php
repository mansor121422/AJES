<?php

namespace App\Models;

use App\Libraries\AcademicYearManager;
use CodeIgniter\Model;

class TeacherSectionModel extends Model
{
    protected $table            = 'teacher_sections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields   = ['teacher_id', 'section_id', 'assignment_role', 'subject_name', 'status'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    public function getInvitesForTeacher(int $teacherId): array
    {
        $builder = $this->db->table($this->table)
            ->select('teacher_sections.*, sections.name as section_name, sections.grade_level, sections.class_schedule')
            ->join('sections', 'sections.id = teacher_sections.section_id')
            ->where('teacher_sections.teacher_id', $teacherId)
            ->where('teacher_sections.status', 'pending');

        $this->scopeActiveAcademicYear($builder);

        return $builder->get()->getResultArray();
    }

    public function getAcceptedSectionsForTeacher(int $teacherId): array
    {
        $builder = $this->db->table($this->table)
            ->select('teacher_sections.*, sections.name as section_name, sections.grade_level, sections.class_schedule')
            ->join('sections', 'sections.id = teacher_sections.section_id')
            ->where('teacher_sections.teacher_id', $teacherId)
            ->where('teacher_sections.status', 'accepted');

        $this->scopeActiveAcademicYear($builder);

        return $builder->get()->getResultArray();
    }

    /**
     * @param \CodeIgniter\Database\BaseBuilder $builder
     */
    private function scopeActiveAcademicYear($builder): void
    {
        $ayId = AcademicYearManager::getActiveId();
        if ($ayId <= 0) {
            return;
        }
        $builder->groupStart()
            ->where('sections.academic_year_id', $ayId)
            ->orWhere('sections.academic_year_id', null)
        ->groupEnd();
    }
}
