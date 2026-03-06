<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoreTables extends Migration
{
    public function up(): void
    {
        // Users
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 191, 'unique' => true],
            'username'       => ['type' => 'VARCHAR', 'constraint' => 50, 'unique' => true],
            'password_hash'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'section_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'guidance_flag'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_active'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'failed_attempts'=> ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'last_failed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users', true);

        // Sections
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 20],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('sections', true);

        // Teacher-Section pivot
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'teacher_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'section_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['teacher_id', 'section_id']);
        $this->forge->createTable('teacher_sections', true);

        // Announcements
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'body'          => ['type' => 'TEXT'],
            'created_by'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'ACTIVE'],
            'audience_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'section_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('announcements', true);

        // Messages (Chat)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'sender_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'receiver_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'content'     => ['type' => 'TEXT'],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'SENT'],
            'is_bot'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('sender_id');
        $this->forge->addKey('receiver_id');
        $this->forge->createTable('messages', true);

        // Records (Guidance/Student/Teacher)
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'student_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'details'    => ['type' => 'TEXT'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('student_id');
        $this->forge->createTable('records', true);

        // Logs (Audit)
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'action_type'  => ['type' => 'VARCHAR', 'constraint' => 50],
            'related_table'=> ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'related_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'details'      => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('logs', true);

        // Notifications
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'           => ['type' => 'VARCHAR', 'constraint' => 50],
            'reference_table'=> ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'reference_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'message'        => ['type' => 'VARCHAR', 'constraint' => 191],
            'is_read'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('notifications', true);

        // Foreign keys (all tables exist now)
        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}users` ADD CONSTRAINT `fk_users_section` FOREIGN KEY (`section_id`) REFERENCES `{$prefix}sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}teacher_sections` ADD CONSTRAINT `fk_teacher_sections_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}teacher_sections` ADD CONSTRAINT `fk_teacher_sections_section` FOREIGN KEY (`section_id`) REFERENCES `{$prefix}sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}announcements` ADD CONSTRAINT `fk_announcements_created_by` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}announcements` ADD CONSTRAINT `fk_announcements_section` FOREIGN KEY (`section_id`) REFERENCES `{$prefix}sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}messages` ADD CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}messages` ADD CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}records` ADD CONSTRAINT `fk_records_student` FOREIGN KEY (`student_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}records` ADD CONSTRAINT `fk_records_created_by` FOREIGN KEY (`created_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}logs` ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}notifications` ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        $drops = [
            "ALTER TABLE `{$prefix}notifications` DROP FOREIGN KEY `fk_notifications_user`",
            "ALTER TABLE `{$prefix}logs` DROP FOREIGN KEY `fk_logs_user`",
            "ALTER TABLE `{$prefix}records` DROP FOREIGN KEY `fk_records_student`",
            "ALTER TABLE `{$prefix}records` DROP FOREIGN KEY `fk_records_created_by`",
            "ALTER TABLE `{$prefix}messages` DROP FOREIGN KEY `fk_messages_sender`",
            "ALTER TABLE `{$prefix}messages` DROP FOREIGN KEY `fk_messages_receiver`",
            "ALTER TABLE `{$prefix}announcements` DROP FOREIGN KEY `fk_announcements_created_by`",
            "ALTER TABLE `{$prefix}announcements` DROP FOREIGN KEY `fk_announcements_section`",
            "ALTER TABLE `{$prefix}teacher_sections` DROP FOREIGN KEY `fk_teacher_sections_teacher`",
            "ALTER TABLE `{$prefix}teacher_sections` DROP FOREIGN KEY `fk_teacher_sections_section`",
            "ALTER TABLE `{$prefix}users` DROP FOREIGN KEY `fk_users_section`",
        ];
        foreach ($drops as $sql) {
            $this->db->query($sql);
        }
        $this->forge->dropTable('notifications', true);
        $this->forge->dropTable('logs', true);
        $this->forge->dropTable('records', true);
        $this->forge->dropTable('messages', true);
        $this->forge->dropTable('announcements', true);
        $this->forge->dropTable('teacher_sections', true);
        $this->forge->dropTable('sections', true);
        $this->forge->dropTable('users', true);
    }
}

