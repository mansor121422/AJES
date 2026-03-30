<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChatMessageAttachments extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('messages', [
            'attachment_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'content'],
            'attachment_url'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'attachment_type'],
            'attachment_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'attachment_url'],
            'attachment_mime' => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true, 'after' => 'attachment_name'],
            'attachment_size' => ['type' => 'INT', 'constraint' => 10, 'null' => true, 'after' => 'attachment_mime'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('messages', [
            'attachment_type',
            'attachment_url',
            'attachment_name',
            'attachment_mime',
            'attachment_size',
        ]);
    }
}

