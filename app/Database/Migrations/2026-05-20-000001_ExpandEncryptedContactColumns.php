<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Encrypted values (ENC: + base64) exceed 30 chars; widen columns so ciphertext is not truncated.
 */
class ExpandEncryptedContactColumns extends Migration
{
    public function up(): void
    {
        $changes = [
            'guardian_contact' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'contact_number' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'guardian_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
        ];

        foreach ($changes as $column => $definition) {
            if ($this->db->fieldExists($column, 'users')) {
                $this->forge->modifyColumn('users', [$column => $definition]);
            }
        }
    }

    public function down(): void
    {
        $revert = [
            'guardian_contact' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'contact_number'   => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'guardian_name'    => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
        ];

        foreach ($revert as $column => $definition) {
            if ($this->db->fieldExists($column, 'users')) {
                $this->forge->modifyColumn('users', [$column => $definition]);
            }
        }
    }
}
