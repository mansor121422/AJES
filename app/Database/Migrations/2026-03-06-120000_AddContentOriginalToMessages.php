<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddContentOriginalToMessages extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('messages', [
            'content_original' => [
                'type'    => 'TEXT',
                'null'    => true,
                'after'   => 'content',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('messages', 'content_original');
    }
}
