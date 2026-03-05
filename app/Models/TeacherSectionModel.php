<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherSectionModel extends Model
{
    protected $table            = 'teacher_sections';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields   = ['teacher_id', 'section_id', 'status'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    public function getInvitesForTeacher(int $teacherId): array
    {
        return $this->db->table($this->table)
            ->select('teacher_sections.*, sections.name as section_name, sections.grade_level')
            ->join('sections', 'sections.id = teacher_sections.section_id')
            ->where('teacher_sections.teacher_id', $teacherId)
            ->where('teacher_sections.status', 'pending')
            ->get()
            ->getResultArray();
    }

    public function getAcceptedSectionsForTeacher(int $teacherId): array
    {
        return $this->db->table($this->table)
            ->select('teacher_sections.*, sections.name as section_name, sections.grade_level')
            ->join('sections', 'sections.id = teacher_sections.section_id')
            ->where('teacher_sections.teacher_id', $teacherId)
            ->where('teacher_sections.status', 'accepted')
            ->get()
            ->getResultArray();
    }
}
