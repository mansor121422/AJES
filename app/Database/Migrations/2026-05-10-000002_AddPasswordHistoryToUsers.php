<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordHistoryToUsers extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('password_history', 'users')) {
            $this->forge->addColumn('users', [
                'password_history' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'password_hash',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('password_history', 'users')) {
            $this->forge->dropColumn('users', 'password_history');
        }
    }
}
