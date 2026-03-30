<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUserPresenceFields extends Migration
{
    public function up(): void
    {
        // Users presence:
        // - `is_online` is set to 1 when user logs in / while session is active
        // - `last_seen_at` stores the last time we observed the session
        // - UI computes offline if `last_seen_at` is older than the timeout
        $this->forge->addColumn('users', [
            'is_online'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'is_active'],
            'last_seen_at'=> ['type' => 'DATETIME', 'null' => true, 'after' => 'is_online'],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', ['is_online', 'last_seen_at']);
    }
}

