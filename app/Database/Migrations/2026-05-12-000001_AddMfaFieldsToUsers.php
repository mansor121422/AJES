<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMfaFieldsToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'mfa_enabled' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
                'after'      => 'admin_privileges',
            ],
            'mfa_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
                'after'      => 'mfa_enabled',
            ],
            'mfa_expires_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'mfa_code',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['mfa_enabled', 'mfa_code', 'mfa_expires_at']);
    }
}
