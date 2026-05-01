<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStudentProfileFieldsToUsers extends Migration
{
    public function up(): void
    {
        $fields = [
            'student_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'grade_level',
            ],
            'birthdate' => [
                'type'  => 'DATE',
                'null'  => true,
                'after' => 'student_id',
            ],
            'age' => [
                'type'       => 'INT',
                'constraint' => 3,
                'null'       => true,
                'after'      => 'birthdate',
            ],
            'address' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'age',
            ],
            'guardian_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 120,
                'null'       => true,
                'after'      => 'address',
            ],
            'guardian_contact' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'after'      => 'guardian_name',
            ],
        ];

        foreach ($fields as $name => $definition) {
            if (! $this->db->fieldExists($name, 'users')) {
                $this->forge->addColumn('users', [$name => $definition]);
            }
        }
    }

    public function down(): void
    {
        foreach (['guardian_contact', 'guardian_name', 'address', 'age', 'birthdate', 'student_id'] as $field) {
            if ($this->db->fieldExists($field, 'users')) {
                $this->forge->dropColumn('users', $field);
            }
        }
    }
}

