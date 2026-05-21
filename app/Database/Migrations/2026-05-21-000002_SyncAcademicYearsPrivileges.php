<?php

namespace App\Database\Migrations;

use App\Libraries\AdminPrivilege;
use App\Libraries\RoleRegistry;
use CodeIgniter\Database\Migration;

class SyncAcademicYearsPrivileges extends Migration
{
    public function up(): void
    {
        $feature = 'academic_years';
        $db      = $this->db;

        foreach ($db->table('roles')->get()->getResultArray() as $row) {
            $slug = strtoupper(trim((string) ($row['slug'] ?? '')));
            if (! in_array($slug, ['SUPER_ADMIN', 'ADMIN', 'PRINCIPAL'], true)) {
                continue;
            }
            $privileges = AdminPrivilege::normalize($row['privileges'] ?? []);
            if (in_array($feature, $privileges, true)) {
                continue;
            }
            $privileges[] = $feature;
            $db->table('roles')->where('id', (int) $row['id'])->update([
                'privileges' => json_encode(array_values(array_unique($privileges)), JSON_UNESCAPED_SLASHES),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        foreach ($db->table('users')->get()->getResultArray() as $user) {
            $role = strtoupper(trim((string) ($user['role'] ?? '')));
            if (! in_array($role, ['SUPER_ADMIN', 'ADMIN'], true)) {
                continue;
            }
            $privileges = AdminPrivilege::normalize($user['admin_privileges'] ?? '');
            if ($privileges === [] || in_array($feature, $privileges, true)) {
                continue;
            }
            $privileges[] = $feature;
            $db->table('users')->where('id', (int) $user['id'])->update([
                'admin_privileges' => json_encode(array_values(array_unique($privileges)), JSON_UNESCAPED_SLASHES),
                'updated_at'       => date('Y-m-d H:i:s'),
            ]);
        }

        RoleRegistry::clearCache();
    }

    public function down(): void
    {
        // No rollback — removing a privilege from live roles is unsafe.
    }
}
