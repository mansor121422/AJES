<?php

namespace App\Database\Seeds;

use App\Libraries\AdminPrivilege;
use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Dev / local default for all seeded accounts (change in production).
        $password = password_hash('123123', PASSWORD_DEFAULT);
        $allAdminPrivileges = json_encode(array_keys(AdminPrivilege::labels()), JSON_UNESCAPED_SLASHES);

        // Try to get any existing section to assign to teacher/student
        $section    = $this->db->table('sections')->get()->getRowArray();
        $sectionId  = $section['id'] ?? null;

        $users = [
            [
                'name'          => 'AJES Super Administrator',
                'email'         => 'superadmin@ajes.local',
                'username'      => 'superadmin',
                'password_hash' => $password,
                'role'          => 'SUPER_ADMIN',
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'System Administrator',
                'email'         => 'admin@ajes.local',
                'username'      => 'admin',
                'password_hash' => $password,
                'role'          => 'ADMIN',
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'AJES Administrator',
                'email'         => 'ajes.admin@ajes.local',
                'username'      => 'ajesadmin',
                'password_hash' => $password,
                'role'          => 'ADMIN',
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Elementary Principal',
                'email'         => 'principal@ajes.local',
                'username'      => 'principal',
                'password_hash' => $password,
                'role'          => 'PRINCIPAL',
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Vice Principal',
                'email'         => 'viceprincipal@ajes.local',
                'username'      => 'viceprincipal',
                'password_hash' => $password,
                'role'          => 'VICE_PRINCIPAL',
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Head Teacher',
                'email'         => 'headteacher@ajes.local',
                'username'      => 'headteacher',
                'password_hash' => $password,
                'role'          => 'HEAD_TEACHER',
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Announcer Staff',
                'email'         => 'announcer@ajes.local',
                'username'      => 'announcer',
                'password_hash' => $password,
                'role'          => 'ANNOUNCER',
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Guidance Counselor',
                'email'         => 'guidance@ajes.local',
                'username'      => 'guidance',
                'password_hash' => $password,
                'role'          => 'GUIDANCE',
                'guidance_flag' => 1,
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Sample Teacher',
                'email'         => 'teacher1@ajes.local',
                'username'      => 'teacher1',
                'password_hash' => $password,
                'role'          => 'TEACHER',
                'section_id'    => $sectionId,
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Sample Student',
                'email'         => 'student1@ajes.local',
                'username'      => 'student1',
                'password_hash' => $password,
                'role'          => 'STUDENT',
                'section_id'    => $sectionId,
                'admin_privileges' => $allAdminPrivileges,
                'is_active'     => 1,
            ],
            [
                'name'          => 'Sample Parent',
                'email'         => 'parent1@ajes.local',
                'username'      => 'parent1',
                'password_hash' => $password,
                'role'          => 'PARENT',
                'admin_privileges' => $allAdminPrivileges,
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

