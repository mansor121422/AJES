<?php

namespace App\Libraries;

class AdminPrivilege
{
    /**
     * Legacy full ADMIN set before new system features were introduced.
     *
     * @return list<string>
     */
    private static function legacyAdminFullSet(): array
    {
        return ['dashboard', 'sections', 'announcements', 'records', 'user_management', 'chat_logs'];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function roleMap(): array
    {
        return [
            'SUPER_ADMIN' => array_keys(self::labels()),
            'ADMIN' => ['dashboard', 'sections', 'announcements', 'records', 'user_management', 'chat_logs'],
            'PRINCIPAL' => ['dashboard', 'announcements'],
            'VICE_PRINCIPAL' => ['dashboard', 'announcements', 'records', 'chat_logs'],
            'HEAD_TEACHER' => ['dashboard', 'announcements', 'records', 'chat_logs'],
            'ANNOUNCER' => ['dashboard', 'announcements'],
            'TEACHER' => ['dashboard', 'announcements', 'teacher_sections'],
            'GUIDANCE' => ['dashboard', 'announcements', 'records'],
            'PARENT' => ['dashboard', 'announcements'],
            'STUDENT' => ['dashboard', 'announcements'],
        ];
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
        $normalizedRole = strtoupper(trim($role));
        $map = self::roleMap();
        return $map[$normalizedRole] ?? [];
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

    public static function canAccess(string $role, mixed $assignedPrivileges, string $requiredPrivilege): bool
    {
        $required = trim($requiredPrivilege);
        if ($required === '') {
            return true;
        }

        $granted = self::effectiveForRole($role, $assignedPrivileges);

        // Backward compatibility: existing users with empty privilege list
        // keep full access until they are explicitly configured.
        if ($granted === []) {
            return true;
        }

        return in_array($required, $granted, true);
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
            return [];
        }

        if (in_array($normalizedRole, ['ADMIN', 'SUPER_ADMIN'], true)) {
            $legacySet = self::legacyAdminFullSet();
            $isLegacyFullAdmin = count(array_diff($legacySet, $granted)) === 0;
            if ($isLegacyFullAdmin) {
                $granted = array_values(array_unique(array_merge($granted, self::allowedForRole($normalizedRole))));
            }
        }

        return self::normalizeForRole($normalizedRole, $granted);
    }
}
