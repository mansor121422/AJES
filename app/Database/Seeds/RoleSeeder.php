<?php

namespace App\Database\Seeds;

use App\Libraries\AdminPrivilege;
use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $db = \Config\Database::connect();
        if ($db->table('roles')->countAllResults() > 0) {
            return;
        }

        $map = AdminPrivilege::defaultRoleMap();
        $dashboardTypes = [
            'SUPER_ADMIN'    => 'admin',
            'ADMIN'          => 'admin',
            'PRINCIPAL'      => 'principal',
            'VICE_PRINCIPAL' => 'vice_principal',
            'HEAD_TEACHER'   => 'head_teacher',
            'ANNOUNCER'      => 'announcer',
            'TEACHER'        => 'teacher',
            'GUIDANCE'       => 'guidance',
            'STUDENT'        => 'student',
        ];
        $displayNames = [
            'SUPER_ADMIN'    => 'Super Admin',
            'ADMIN'          => 'Technical / System Administrator',
            'PRINCIPAL'      => 'Principal',
            'VICE_PRINCIPAL' => 'Vice Principal',
            'HEAD_TEACHER'   => 'Head Teacher',
            'ANNOUNCER'      => 'Announcer',
            'TEACHER'        => 'Teacher',
            'GUIDANCE'       => 'Guidance Counselor',
            'STUDENT'        => 'Student',
        ];

        $order = 0;
        foreach ($map as $slug => $privileges) {
            $db->table('roles')->insert([
                'slug'           => $slug,
                'name'           => $displayNames[$slug] ?? $slug,
                'privileges'     => json_encode($privileges, JSON_UNESCAPED_SLASHES),
                'dashboard_type' => $dashboardTypes[$slug] ?? 'generic',
                'is_system'      => 1,
                'sort_order'     => $order++,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
