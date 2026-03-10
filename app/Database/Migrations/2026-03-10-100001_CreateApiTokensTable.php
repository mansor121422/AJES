<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiTokensTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'token'      => ['type' => 'VARCHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('token');
        $this->forge->addKey('user_id');
        $this->forge->addKey('expires_at');
        $this->forge->createTable('api_tokens', true);

        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}api_tokens` ADD CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}api_tokens` DROP FOREIGN KEY `fk_api_tokens_user`");
        $this->forge->dropTable('api_tokens', true);
    }
}
