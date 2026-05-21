<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStudentTypeAndPreviousSchool extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('student_type', 'users')) {
            $this->forge->addColumn('users', [
                'student_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'null'       => true,
                    'after'      => 'student_id',
                ],
            ]);
        }

        if (! $this->db->fieldExists('previous_school', 'users')) {
            $this->forge->addColumn('users', [
                'previous_school' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 191,
                    'null'       => true,
                    'after'      => 'student_type',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('previous_school', 'users')) {
            $this->forge->dropColumn('users', 'previous_school');
        }
        if ($this->db->fieldExists('student_type', 'users')) {
            $this->forge->dropColumn('users', 'student_type');
        }
    }
}
