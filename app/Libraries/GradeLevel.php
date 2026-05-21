<?php

namespace App\Libraries;

/**
 * Normalizes and advances DepEd-style grade levels (1–6).
 */
class GradeLevel
{
    public const MIN = 1;
    public const MAX = 6;

    public static function normalize(?string $grade): string
    {
        $g = trim((string) $grade);
        if ($g === '') {
            return '';
        }
        if (preg_match('/^([1-6])$/', $g, $m)) {
            return $m[1];
        }
        if (preg_match('/grade\s*([1-6])/i', $g, $m)) {
            return $m[1];
        }

        return '';
    }

    public static function label(?string $grade): string
    {
        $d = self::normalize($grade);

        return $d !== '' ? 'Grade ' . $d : '—';
    }

    /** Next grade digit, or null when student has completed Grade 6. */
    public static function next(?string $grade): ?string
    {
        $d = self::normalize($grade);
        if ($d === '') {
            return null;
        }
        $n = (int) $d;
        if ($n >= self::MAX) {
            return null;
        }

        return (string) ($n + 1);
    }

    public static function isGraduating(?string $grade): bool
    {
        return self::normalize($grade) === (string) self::MAX;
    }

    /**
     * @return list<string>
     */
    public static function allDigits(): array
    {
        return ['1', '2', '3', '4', '5', '6'];
    }
}
