<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditSessionTransactionTables extends Migration
{
    public function up(): void
    {
        // Activity logs — records every user action (page visit, CRUD, etc.)
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'module'      => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'url'         => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'method'      => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'ip_address'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'user_agent'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'details'     => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('action');
        $this->forge->addKey('created_at');
        $this->forge->createTable('activity_logs', true);

        // User sessions — tracks active sessions
        $this->forge->addField([
            'id'           => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'session_id'   => ['type' => 'VARCHAR', 'constraint' => 128],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'started_at'   => ['type' => 'DATETIME', 'null' => true],
            'last_activity'=> ['type' => 'DATETIME', 'null' => true],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('session_id');
        $this->forge->createTable('user_sessions', true);

        // Transactions log — records commit/rollback of business transactions
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'operation'   => ['type' => 'VARCHAR', 'constraint' => 60],
            'target_table'=> ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'target_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'error'       => ['type' => 'TEXT', 'null' => true],
            'duration_ms' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('transactions_log', true);

        // Add ip_address to existing logs table for intrusion detection
        $prefix = $this->db->getPrefix();
        try {
            $hasIp = $this->db->query("SHOW COLUMNS FROM `{$prefix}logs` LIKE 'ip_address'")->getNumRows() > 0;
            if (! $hasIp) {
                $this->forge->addColumn('logs', [
                    'ip_address' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'details'],
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Lab6 migration: ' . $e->getMessage());
        }
    }

    public function down(): void
    {
        $this->forge->dropTable('transactions_log', true);
        $this->forge->dropTable('user_sessions', true);
        $this->forge->dropTable('activity_logs', true);

        try {
            $this->forge->dropColumn('logs', 'ip_address');
        } catch (\Throwable $e) {
        }
    }
}
