<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddProfileFieldsToUsers extends Migration
{
    public function up(): void
    {
        $fields = [
            'contact_number' => [
                'type' => 'VARCHAR',
                'constraint' => 30,
                'null' => true,
                'after' => 'email',
            ],
            'bio' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'contact_number',
            ],
            'profile_photo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'bio',
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
        foreach (['profile_photo', 'bio', 'contact_number'] as $field) {
            if ($this->db->fieldExists($field, 'users')) {
                $this->forge->dropColumn('users', $field);
            }
        }
    }
}

