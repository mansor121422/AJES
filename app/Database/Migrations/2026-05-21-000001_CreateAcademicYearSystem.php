<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAcademicYearSystem extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'start_date' => ['type' => 'DATE', 'null' => true],
            'end_date'   => ['type' => 'DATE', 'null' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'planning'],
            'closed_at'  => ['type' => 'DATETIME', 'null' => true],
            'closed_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'notes'      => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('label');
        $this->forge->addKey('status');
        $this->forge->createTable('academic_years', true);

        if (! $this->db->fieldExists('academic_year_id', 'sections')) {
            $this->forge->addColumn('sections', [
                'academic_year_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'grade_level',
                ],
            ]);
        }

        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'academic_year_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'grade_level'           => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'section_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'section_name_snapshot' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'subjects_snapshot'     => ['type' => 'TEXT', 'null' => true],
            'outcome'               => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'enrolled'],
            'is_current'            => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'academic_year_id']);
        $this->forge->addKey('is_current');
        $this->forge->addKey('academic_year_id');
        $this->forge->createTable('student_enrollments', true);

        $prefix = $this->db->getPrefix();
        $this->db->query("ALTER TABLE `{$prefix}sections` ADD CONSTRAINT `fk_sections_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `{$prefix}academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}student_enrollments` ADD CONSTRAINT `fk_enrollments_user` FOREIGN KEY (`user_id`) REFERENCES `{$prefix}users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}student_enrollments` ADD CONSTRAINT `fk_enrollments_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `{$prefix}academic_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}student_enrollments` ADD CONSTRAINT `fk_enrollments_section` FOREIGN KEY (`section_id`) REFERENCES `{$prefix}sections` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE `{$prefix}academic_years` ADD CONSTRAINT `fk_academic_years_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `{$prefix}users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE");

        $this->seedInitialAcademicYear();
    }

    public function down(): void
    {
        $prefix = $this->db->getPrefix();
        $drops = [
            "ALTER TABLE `{$prefix}student_enrollments` DROP FOREIGN KEY `fk_enrollments_section`",
            "ALTER TABLE `{$prefix}student_enrollments` DROP FOREIGN KEY `fk_enrollments_academic_year`",
            "ALTER TABLE `{$prefix}student_enrollments` DROP FOREIGN KEY `fk_enrollments_user`",
            "ALTER TABLE `{$prefix}sections` DROP FOREIGN KEY `fk_sections_academic_year`",
            "ALTER TABLE `{$prefix}academic_years` DROP FOREIGN KEY `fk_academic_years_closed_by`",
        ];
        foreach ($drops as $sql) {
            try {
                $this->db->query($sql);
            } catch (\Throwable $e) {
            }
        }

        $this->forge->dropTable('student_enrollments', true);
        if ($this->db->fieldExists('academic_year_id', 'sections')) {
            $this->forge->dropColumn('sections', 'academic_year_id');
        }
        $this->forge->dropTable('academic_years', true);
    }

    private function seedInitialAcademicYear(): void
    {
        $year  = (int) date('Y');
        $month = (int) date('n');
        if ($month >= 6) {
            $startYear = $year;
            $endYear   = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear   = $year;
        }
        $label = $startYear . '–' . $endYear;
        $now   = date('Y-m-d H:i:s');

        $this->db->table('academic_years')->insert([
            'label'      => $label,
            'start_date' => $startYear . '-06-01',
            'end_date'   => $endYear . '-03-31',
            'status'     => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $ayId = (int) $this->db->insertID();

        $this->db->table('sections')->update(['academic_year_id' => $ayId]);

        $studentRoles = ['STUDENT'];
        $roleRows = $this->db->table('roles')->get()->getResultArray();
        foreach ($roleRows as $row) {
            $privileges = json_decode((string) ($row['privileges'] ?? ''), true);
            if (! is_array($privileges)) {
                continue;
            }
            if (in_array('student_dashboard', $privileges, true) || ($row['dashboard_type'] ?? '') === 'student') {
                $slug = strtoupper(trim((string) ($row['slug'] ?? '')));
                if ($slug !== '' && ! in_array($slug, $studentRoles, true)) {
                    $studentRoles[] = $slug;
                }
            }
        }

        $students = $this->db->table('users')
            ->select('id, grade_level, section_id')
            ->whereIn('role', $studentRoles)
            ->where('deleted_at', null)
            ->get()
            ->getResultArray();

        $sectionNames = [];
        foreach ($this->db->table('sections')->get()->getResultArray() as $sec) {
            $sectionNames[(int) $sec['id']] = $sec;
        }

        foreach ($students as $student) {
            $sectionId = (int) ($student['section_id'] ?? 0);
            $sec       = $sectionId > 0 ? ($sectionNames[$sectionId] ?? null) : null;
            $snapshot  = null;
            if ($sec !== null && ! empty($sec['class_schedule'])) {
                $snapshot = is_string($sec['class_schedule']) ? $sec['class_schedule'] : json_encode($sec['class_schedule']);
            }

            $this->db->table('student_enrollments')->insert([
                'user_id'               => (int) $student['id'],
                'academic_year_id'      => $ayId,
                'grade_level'           => $student['grade_level'] ?? null,
                'section_id'            => $sectionId > 0 ? $sectionId : null,
                'section_name_snapshot' => $sec['name'] ?? null,
                'subjects_snapshot'     => $snapshot,
                'outcome'               => 'enrolled',
                'is_current'            => 1,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);
        }
    }
}
