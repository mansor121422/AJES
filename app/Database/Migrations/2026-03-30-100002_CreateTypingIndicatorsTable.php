<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTypingIndicatorsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'from_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'to_user_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'is_typing'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        // One row per (from_user_id, to_user_id) - enforce via UNIQUE.
        // (Must not be a second PRIMARY KEY because `id` is auto_increment.)
        $this->forge->addKey(['from_user_id', 'to_user_id'], false, true);

        $this->forge->createTable('typing_indicators', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('typing_indicators', true);
    }
}

