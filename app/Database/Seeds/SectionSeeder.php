<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'name'        => 'Grade 1 - A',
                'grade_level' => '1',
            ],
            [
                'name'        => 'Grade 3 - A',
                'grade_level' => '3',
            ],
            [
                'name'        => 'Grade 6 - A',
                'grade_level' => '6',
            ],
        ];

        foreach ($sections as $section) {
            $this->db->table('sections')->insert($section);
        }
    }
}

