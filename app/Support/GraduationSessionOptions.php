<?php

namespace App\Support;

class GraduationSessionOptions
{
    /**
     * @return list<string>
     */
    public static function values(int $startYear = 2004, ?int $endYear = null): array
    {
        $endYear ??= (int) now()->year;

        if ($endYear < $startYear) {
            return [];
        }

        $sessions = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $sessions[] = self::format($year);
        }

        return array_reverse($sessions);
    }

    public static function format(int $startYear): string
    {
        return sprintf('%d/%d', $startYear, $startYear + 1);
    }
}
