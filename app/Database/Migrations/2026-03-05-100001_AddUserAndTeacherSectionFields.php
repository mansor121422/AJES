<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserAndTeacherSectionFields extends Migration
{
    public function up(): void
    {
        // Users: full name parts and student info for records display
        $this->forge->addColumn('users', [
            'surname'         => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'name'],
            'first_name'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'surname'],
            'middle_initial'  => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'first_name'],
            'name_suffix'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'middle_initial'],
            'gender'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'name_suffix'],
            'grade_level'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'gender'],
        ]);

        // Teacher-Section: invite/accept flow
        $this->forge->addColumn('teacher_sections', [
            'status'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending', 'after' => 'section_id'],
            'created_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
            'updated_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'created_at'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['surname', 'first_name', 'middle_initial', 'name_suffix', 'gender', 'grade_level']);
        $this->forge->dropColumn('teacher_sections', ['status', 'created_at', 'updated_at']);
    }
}
