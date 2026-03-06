<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMessageHidesTable extends Migration
{
    public function up(): void
    {
        $prefix = $this->db->getPrefix();
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'message_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['message_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('message_hides', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('message_hides', true);
    }
}
