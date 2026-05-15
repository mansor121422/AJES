<?php

namespace App\Libraries;

class AdminPrivilege
{
    /**
     * Historical full ADMIN set before system modules were introduced.
     *
     * @return list<string>
     */
    private static function historicalAdminBaseSet(): array
    {
        return [
            'dashboard',
            'sections',
            'announcements',
            'records',
            'user_management',
            'chat_logs',
        ];
    }

    /**
     * Built-in defaults used before roles table exists or as seed source.
     *
     * @return array<string, list<string>>
     */
    public static function defaultRoleMap(): array
    {
        return [
            'SUPER_ADMIN' => array_keys(self::labels()),
            'ADMIN' => array_keys(self::labels()),
            'PRINCIPAL' => ['dashboard', 'announcements', 'records', 'chat_logs'],
            'VICE_PRINCIPAL' => ['dashboard', 'announcements', 'records', 'chat_logs'],
            'HEAD_TEACHER' => ['dashboard', 'announcements', 'records', 'chat_logs'],
            'ANNOUNCER' => ['dashboard', 'announcements'],
            'TEACHER' => ['dashboard', 'announcements', 'teacher_sections'],
            'GUIDANCE' => ['dashboard', 'announcements', 'records'],
            'STUDENT' => ['dashboard', 'announcements'],
            'STAFF'   => array_keys(self::labels()),
        ];
    }

    /**
     * Role slug => default privileges (from DB when available).
     *
     * @return array<string, list<string>>
     */
    public static function roleMap(): array
    {
        $map = [];
        foreach (RoleRegistry::all() as $slug => $row) {
            $parsed = json_decode((string) ($row['privileges'] ?? ''), true);
            $map[$slug] = self::normalize(is_array($parsed) ? $parsed : []);
        }

        return $map !== [] ? $map : self::defaultRoleMap();
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'sections' => 'Sections',
            'announcements' => 'Announcements',
            'teacher_sections' => 'Teacher Sections',
            'records' => 'Records',
            'user_management' => 'User Management',
            'chat_logs' => 'Chat Logs',
            'system_settings' => 'System Settings',
            'chatbot_management' => 'Chatbot Management',
            'backup_restore' => 'Backup & Restore',
            'security_logs' => 'Security Logs',
            'role_management' => 'Role Management',
        ];
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    public static function normalize(mixed $value): array
    {
        $decoded = $value;
        if (is_string($value)) {
            $parsed = json_decode($value, true);
            $decoded = is_array($parsed) ? $parsed : [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        $allowed = array_keys(self::labels());
        $aliases = [
            'admin_dashboard' => 'dashboard',
        ];
        $clean = [];
        foreach ($decoded as $item) {
            if (! is_string($item)) {
                continue;
            }
            $key = trim($item);
            if (isset($aliases[$key])) {
                $key = $aliases[$key];
            }
            if ($key !== '' && in_array($key, $allowed, true)) {
                $clean[] = $key;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * @return list<string>
     */
    public static function allowedForRole(string $role): array
    {
        return RoleRegistry::privilegesForRole($role);
    }

    /**
     * @return list<string>
     */
    public static function allowedForRoleStatic(string $role): array
    {
        $normalizedRole = strtoupper(trim($role));
        $map = self::defaultRoleMap();

        return $map[$normalizedRole] ?? [];
    }

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return RoleRegistry::roleOptions();
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    public static function normalizeForRole(string $role, mixed $value): array
    {
        $clean = self::normalize($value);
        $allowed = self::allowedForRole($role);
        if ($allowed === []) {
            return [];
        }
        return array_values(array_filter($clean, static fn (string $key): bool => in_array($key, $allowed, true)));
    }

    /**
     * CRUD actions that can be assigned per feature.
     */
    public static function crudActions(): array
    {
        return ['read', 'create', 'update', 'delete'];
    }

    /**
     * Features that support CRUD-level permission granularity.
     */
    public static function crudFeatures(): array
    {
        return ['user_management', 'sections', 'records', 'announcements'];
    }

    /**
     * Check if a role/user can access a feature, optionally at a specific CRUD action level.
     *
     * Usage:
     *   canAccess('ADMIN', $privs, 'user_management')          — feature-level check
     *   canAccess('ADMIN', $privs, 'user_management:delete')    — action-level check
     */
    public static function canAccess(string $role, mixed $assignedPrivileges, string $requiredPrivilege): bool
    {
        $normalizedRole = strtoupper(trim($role));
        $required = trim($requiredPrivilege);
        if ($required === '') {
            return true;
        }

        $feature = $required;
        $action  = '';
        if (str_contains($required, ':')) {
            [$feature, $action] = explode(':', $required, 2);
        }

        $granted = self::effectiveForRole($normalizedRole, $assignedPrivileges);

        if ($granted === []) {
            return in_array($normalizedRole, ['ADMIN', 'SUPER_ADMIN'], true);
        }

        if (! in_array($feature, $granted, true)) {
            return false;
        }

        // If no specific action is requested, feature-level access is enough.
        if ($action === '') {
            return true;
        }

        // ADMIN/SUPER_ADMIN: all CRUD actions are implicitly granted.
        if (in_array($normalizedRole, ['ADMIN', 'SUPER_ADMIN'], true)) {
            return true;
        }

        // Check for explicit action grant (e.g. "user_management:delete" in privileges).
        if (in_array($feature . ':' . $action, $granted, true)) {
            return true;
        }

        // If no action-level entries exist for this feature, the feature grant implies all actions.
        $hasAnyAction = false;
        foreach ($granted as $g) {
            if (str_starts_with($g, $feature . ':')) {
                $hasAnyAction = true;
                break;
            }
        }

        return ! $hasAnyAction;
    }

    /**
     * Runtime privileges used for menu/render/access checks.
     * Auto-upgrades legacy full ADMIN sets to include newly added admin features.
     *
     * @param mixed $assignedPrivileges
     * @return list<string>
     */
    public static function effectiveForRole(string $role, mixed $assignedPrivileges): array
    {
        $normalizedRole = strtoupper(trim($role));
        $granted = self::normalize($assignedPrivileges);

        if ($granted === []) {
            // Backward compatibility:
            // - ADMIN/SUPER_ADMIN with empty stored privileges => full access marker ([]).
            // - Other roles with empty stored privileges => fall back to role default privileges.
            if (in_array($normalizedRole, ['ADMIN', 'SUPER_ADMIN'], true)) {
                return [];
            }
            return self::allowedForRole($normalizedRole);
        }

        if (in_array($normalizedRole, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $historicalSet = self::historicalAdminBaseSet();
            $isHistoricalFullAdmin = count(array_diff($historicalSet, $granted)) === 0;
            if ($isHistoricalFullAdmin) {
                $granted = array_values(array_unique(array_merge($granted, self::allowedForRole($normalizedRole))));
            }
        }

        return self::normalizeForRole($normalizedRole, $granted);
    }
}
