<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLockedUntilToUsers extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('locked_until', 'users')) {
            $this->forge->addColumn('users', [
                'locked_until' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'last_failed_at',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('locked_until', 'users')) {
            $this->forge->dropColumn('users', 'locked_until');
        }
    }
}
