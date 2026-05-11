<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAdminPrivilegesToUsers extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('admin_privileges', 'users')) {
            $this->forge->addColumn('users', [
                'admin_privileges' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'role',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('admin_privileges', 'users')) {
            $this->forge->dropColumn('users', 'admin_privileges');
        }
    }
}
