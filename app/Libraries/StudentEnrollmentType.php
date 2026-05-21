<?php

namespace App\Libraries;

/**
 * Student enrollment category for create/edit and Students Log.
 */
class StudentEnrollmentType
{
    public const NEW_STUDENT = 'new';

    public const TRANSFEREE = 'transferee';

    public const OLD_STUDENT = 'old';

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        return [
            self::NEW_STUDENT  => 'New student',
            self::TRANSFEREE   => 'Transferee',
            self::OLD_STUDENT  => 'Old student (returning)',
        ];
    }

    public static function isValid(string $type): bool
    {
        return array_key_exists($type, self::options());
    }

    public static function requiresPreviousSchool(string $type): bool
    {
        return in_array($type, [self::NEW_STUDENT, self::TRANSFEREE], true);
    }

    public static function label(string $type): string
    {
        return self::options()[$type] ?? '';
    }
}
