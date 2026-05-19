<?php

namespace App\Libraries;

use App\Models\UserModel;

class SectionEnrollment
{
    public const MAX_STUDENTS = 30;

    /**
     * Role slugs treated as students for section enrollment counts.
     *
     * @return list<string>
     */
    public static function studentRoleSlugs(): array
    {
        static $slugs = null;
        if ($slugs !== null) {
            return $slugs;
        }

        $slugs = ['STUDENT'];
        foreach (RoleRegistry::all() as $slug => $row) {
            if (RoleRegistry::dashboardType($slug) === 'student' && ! in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    public static function countStudentsInSection(int $sectionId): int
    {
        if ($sectionId <= 0) {
            return 0;
        }

        return (new UserModel())
            ->where('section_id', $sectionId)
            ->whereIn('role', self::studentRoleSlugs())
            ->countAllResults();
    }

    public static function remainingSlots(int $sectionId): int
    {
        return max(0, self::MAX_STUDENTS - self::countStudentsInSection($sectionId));
    }

    public static function isFull(int $sectionId): bool
    {
        return self::countStudentsInSection($sectionId) >= self::MAX_STUDENTS;
    }

    public static function capacityMessage(int $sectionId): string
    {
        $count = self::countStudentsInSection($sectionId);

        return 'This section already has ' . $count . ' student' . ($count === 1 ? '' : 's') . '. Maximum is ' . self::MAX_STUDENTS . '.';
    }

    /**
     * @param array<string, mixed> $user
     */
    public static function isStudentUser(array $user): bool
    {
        $role = strtoupper(trim((string) ($user['role'] ?? '')));

        return in_array($role, self::studentRoleSlugs(), true);
    }
}
