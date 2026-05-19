<?php

namespace App\Libraries;

/**
 * Grade-based minimum age: Grade 1 = 5, Grade 2 = 6, … Grade 6 = 10.
 * Maximum age is below 100 (valid ages 0–99; students typically within grade range).
 */
class StudentAgeRules
{
    public const MAX_AGE = 99;

    /** Minimum age for elementary grades 1–6. */
    public static function minAgeForGrade(int|string $gradeLevel): ?int
    {
        $grade = (int) $gradeLevel;
        if ($grade < 1 || $grade > 6) {
            return null;
        }

        return $grade + 4;
    }

    public static function computeAgeFromBirthdate(string $birthdate): ?int
    {
        if ($birthdate === '') {
            return null;
        }
        try {
            $dob = new \DateTimeImmutable($birthdate);
            $now = new \DateTimeImmutable('today');
            if ($dob > $now) {
                return null;
            }

            return (int) $dob->diff($now)->y;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return string|null Error message, or null if valid.
     */
    public static function validate(int|string $gradeLevel, string $birthdate): ?string
    {
        $grade = (int) $gradeLevel;
        $min   = self::minAgeForGrade($grade);
        if ($min === null) {
            return 'Choose a grade level from Grade 1 to Grade 6.';
        }

        if (trim($birthdate) === '') {
            return 'Birthdate is required for students.';
        }

        $age = self::computeAgeFromBirthdate($birthdate);
        if ($age === null) {
            return 'Enter a valid birthdate that is not in the future.';
        }

        if ($age < $min) {
            return 'For Grade ' . $grade . ', the student must be at least ' . $min
                . ' years old (current age from birthdate: ' . $age . ').';
        }

        if ($age > self::MAX_AGE) {
            return 'Student age must be ' . self::MAX_AGE . ' years or less (less than 100).';
        }

        return null;
    }

    public static function hintForGrade(int|string $gradeLevel): string
    {
        $min = self::minAgeForGrade($gradeLevel);
        if ($min === null) {
            return 'Select a grade to see the required age range.';
        }

        $grade = (int) $gradeLevel;

        return 'Grade ' . $grade . ': age must be at least ' . $min . ' and at most ' . self::MAX_AGE . ' years.';
    }
}
