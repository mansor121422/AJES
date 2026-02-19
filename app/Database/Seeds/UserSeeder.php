<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = password_hash('123123', PASSWORD_DEFAULT);

        // Try to get any existing section to assign to teacher/student
        $section    = $this->db->table('sections')->get()->getRowArray();
        $sectionId  = $section['id'] ?? null;

        $users = [
            [
                'name'          => 'System Administrator',
                'email'         => 'admin@ajes.local',
                'username'      => 'admin',
                'password_hash' => $password,
                'role'          => 'ADMIN',
                'is_active'     => 1,
            ],
            [
                'name'          => 'Elementary Principal',
                'email'         => 'principal@ajes.local',
                'username'      => 'principal',
                'password_hash' => $password,
                'role'          => 'PRINCIPAL',
                'is_active'     => 1,
            ],
            [
                'name'          => 'Announcer Staff',
                'email'         => 'announcer@ajes.local',
                'username'      => 'announcer',
                'password_hash' => $password,
                'role'          => 'ANNOUNCER',
                'is_active'     => 1,
            ],
            [
                'name'          => 'Guidance Counselor',
                'email'         => 'guidance@ajes.local',
                'username'      => 'guidance',
                'password_hash' => $password,
                'role'          => 'GUIDANCE',
                'guidance_flag' => 1,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Sample Teacher',
                'email'         => 'teacher1@ajes.local',
                'username'      => 'teacher1',
                'password_hash' => $password,
                'role'          => 'TEACHER',
                'section_id'    => $sectionId,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Sample Student',
                'email'         => 'student1@ajes.local',
                'username'      => 'student1',
                'password_hash' => $password,
                'role'          => 'STUDENT',
                'section_id'    => $sectionId,
                'is_active'     => 1,
            ],
        ];

        foreach ($users as $user) {
            $exists = $this->db->table('users')
                ->where('email', $user['email'])
                ->get()
                ->getRowArray();

            if (! $exists) {
                $this->db->table('users')->insert($user);
            }
        }
    }
}

