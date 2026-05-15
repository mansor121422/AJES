<?php

namespace App\Libraries;

use App\Models\RoleModel;

/**
 * Loads role definitions from the database (with static fallback).
 */
class RoleRegistry
{
    private static ?array $cache = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];
        try {
            $model = new RoleModel();
            foreach ($model->orderBy('sort_order', 'ASC')->findAll() as $row) {
                $slug = strtoupper((string) ($row['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                self::$cache[$slug] = $row;
            }
        } catch (\Throwable $e) {
            log_message('error', 'RoleRegistry::all: ' . $e->getMessage());
        }

        if (self::$cache === []) {
            foreach (AdminPrivilege::defaultRoleMap() as $slug => $privileges) {
                self::$cache[$slug] = [
                    'slug'           => $slug,
                    'name'           => $slug,
                    'privileges'     => json_encode($privileges),
                    'dashboard_type' => self::defaultDashboardType($slug),
                    'is_system'      => 1,
                ];
            }
        }

        return self::$cache;
    }

    public static function exists(string $slug): bool
    {
        return isset(self::all()[strtoupper(trim($slug))]);
    }

    public static function displayName(string $slug): string
    {
        $row = self::all()[strtoupper(trim($slug))] ?? null;

        return $row ? (string) ($row['name'] ?? $slug) : $slug;
    }

    /**
     * @return list<string>
     */
    public static function privilegesForRole(string $slug): array
    {
        $row = self::all()[strtoupper(trim($slug))] ?? null;
        if (! $row) {
            return AdminPrivilege::allowedForRoleStatic($slug);
        }
        $parsed = json_decode((string) ($row['privileges'] ?? ''), true);

        return AdminPrivilege::normalize(is_array($parsed) ? $parsed : []);
    }

    public static function dashboardType(string $slug): string
    {
        $row = self::all()[strtoupper(trim($slug))] ?? null;
        $type = $row ? (string) ($row['dashboard_type'] ?? 'generic') : self::defaultDashboardType($slug);

        return $type !== '' ? $type : 'generic';
    }

    public static function dashboardUrl(string $slug, mixed $userPrivileges = null): string
    {
        $granted = AdminPrivilege::effectiveForRole($slug, $userPrivileges);

        return match (self::dashboardType($slug)) {
            'admin'          => self::firstGrantedUrl($granted, ['dashboard/admin'], 'dashboard/admin'),
            'principal'      => self::firstGrantedUrl($granted, ['dashboard/principal'], 'dashboard/principal'),
            'vice_principal' => self::firstGrantedUrl($granted, ['dashboard/vice-principal'], 'dashboard/vice-principal'),
            'head_teacher'   => self::firstGrantedUrl($granted, ['dashboard/head-teacher'], 'dashboard/head-teacher'),
            'teacher'        => self::firstGrantedUrl($granted, ['dashboard/teacher'], 'dashboard/teacher'),
            'student'        => self::firstGrantedUrl($granted, ['dashboard/student'], 'dashboard/student'),
            'guidance'       => self::firstGrantedUrl($granted, ['dashboard/guidance'], 'dashboard/guidance'),
            'announcer'      => self::firstGrantedUrl($granted, ['dashboard/announcer'], 'dashboard/announcer'),
            default          => base_url('dashboard'),
        };
    }

    /**
     * @return array<string, string> slug => display name
     */
    public static function roleOptions(): array
    {
        $out = [];
        foreach (self::all() as $slug => $row) {
            $out[$slug] = (string) ($row['name'] ?? $slug);
        }

        return $out;
    }

    public static function dashboardTypeOptions(): array
    {
        return [
            'admin'          => 'Admin (system)',
            'principal'      => 'Principal',
            'vice_principal' => 'Vice Principal',
            'head_teacher'   => 'Head Teacher',
            'teacher'        => 'Teacher',
            'student'        => 'Student',
            'guidance'       => 'Guidance',
            'announcer'      => 'Announcer',
            'generic'        => 'Generic / default',
        ];
    }

    private static function defaultDashboardType(string $slug): string
    {
        return match (strtoupper(trim($slug))) {
            'SUPER_ADMIN', 'ADMIN' => 'admin',
            'PRINCIPAL' => 'principal',
            'VICE_PRINCIPAL' => 'vice_principal',
            'HEAD_TEACHER' => 'head_teacher',
            'TEACHER' => 'teacher',
            'STUDENT' => 'student',
            'GUIDANCE' => 'guidance',
            'ANNOUNCER' => 'announcer',
            default => 'generic',
        };
    }

    /**
     * @param list<string> $granted
     * @param list<string> $candidates path without base_url
     */
    private static function firstGrantedUrl(array $granted, array $candidates, string $fallback): string
    {
        $hasFull = $granted === [];
        if ($hasFull || in_array('dashboard', $granted, true)) {
            return base_url($fallback);
        }

        return base_url('chat');
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
