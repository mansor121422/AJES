<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSectionClassSchedule extends Migration
{
    public function up(): void
    {
        if (! $this->db->fieldExists('class_schedule', 'sections')) {
            $this->forge->addColumn('sections', [
                'class_schedule' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'grade_level',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('class_schedule', 'sections')) {
            $this->forge->dropColumn('sections', 'class_schedule');
        }
    }
}
