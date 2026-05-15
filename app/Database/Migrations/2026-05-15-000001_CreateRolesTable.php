<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRolesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'privileges'     => ['type' => 'TEXT', 'null' => true],
            'dashboard_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'generic'],
            'is_system'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'sort_order'     => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('roles', true);

        // Allow longer custom role slugs on users.
        $this->forge->modifyColumn('users', [
            'role' => ['type' => 'VARCHAR', 'constraint' => 50],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropTable('roles', true);
        $this->forge->modifyColumn('users', [
            'role' => ['type' => 'VARCHAR', 'constraint' => 20],
        ]);
    }
}
