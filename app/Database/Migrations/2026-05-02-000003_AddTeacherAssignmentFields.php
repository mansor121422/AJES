<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTeacherAssignmentFields extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('assignment_role', 'teacher_sections')) {
            $this->forge->addColumn('teacher_sections', [
                'assignment_role' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'ADVISER',
                    'after'      => 'section_id',
                ],
            ]);
        }

        if (! $this->db->fieldExists('subject_name', 'teacher_sections')) {
            $this->forge->addColumn('teacher_sections', [
                'subject_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'assignment_role',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('subject_name', 'teacher_sections')) {
            $this->forge->dropColumn('teacher_sections', 'subject_name');
        }
        if ($this->db->fieldExists('assignment_role', 'teacher_sections')) {
            $this->forge->dropColumn('teacher_sections', 'assignment_role');
        }
    }
}

