<?php

declare(strict_types=1);

namespace GoodMaven\Arabicable\Support\Text;

use InvalidArgumentException;

class HijriDateConverter
{
    /**
     * @return array{year: int, month: int, day: int}
     */
    public function gregorianToHijri(int $year, int $month, int $day): array
    {
        $this->assertGregorianDate($year, $month, $day);

        $julianDay = $this->gregorianToJulianDay($year, $month, $day);

        $l = $julianDay - 1948440 + 10632;
        $n = intdiv($l - 1, 10631);
        $l = $l - (10631 * $n) + 354;

        $j = intdiv(10985 - $l, 5316) * intdiv(50 * $l, 17719)
            + intdiv($l, 5670) * intdiv(43 * $l, 15238);

        $l = $l
            - intdiv((30 - $j), 15) * intdiv(17719 * $j, 50)
            - intdiv($j, 16) * intdiv(15238 * $j, 43)
            + 29;

        $hijriMonth = intdiv(24 * $l, 709);
        $hijriDay = $l - intdiv(709 * $hijriMonth, 24);
        $hijriYear = (30 * $n) + $j - 30;

        return [
            'year' => $hijriYear,
            'month' => $hijriMonth,
            'day' => $hijriDay,
        ];
    }

    /**
     * @return array{year: int, month: int, day: int}
     */
    public function hijriToGregorian(int $year, int $month, int $day): array
    {
        $this->assertHijriDate($year, $month, $day);

        $julianDay = (int) (
            floor((11 * $year + 3) / 30)
            + (354 * $year)
            + (30 * $month)
            - floor(($month - 1) / 2)
            + $day
            + 1948440
            - 385
        );

        $gregorian = $this->julianDayToGregorian($julianDay);
        [$gregorianMonth, $gregorianDay, $gregorianYear] = array_map('intval', explode('/', $gregorian));

        return [
            'year' => $gregorianYear,
            'month' => $gregorianMonth,
            'day' => $gregorianDay,
        ];
    }

    private function assertGregorianDate(int $year, int $month, int $day): void
    {
        if (! checkdate($month, $day, $year)) {
            throw new InvalidArgumentException('The Gregorian date is invalid.');
        }
    }

    private function assertHijriDate(int $year, int $month, int $day): void
    {
        if ($year < 1 || $month < 1 || $month > 12 || $day < 1 || $day > 30) {
            throw new InvalidArgumentException('The Hijri date is invalid.');
        }
    }

    private function gregorianToJulianDay(int $year, int $month, int $day): int
    {
        if (function_exists('gregoriantojd')) {
            return gregoriantojd($month, $day, $year);
        }

        $monthAdjustment = intdiv(14 - $month, 12);
        $adjustedYear = $year + 4800 - $monthAdjustment;
        $adjustedMonth = $month + (12 * $monthAdjustment) - 3;

        return $day
            + intdiv((153 * $adjustedMonth) + 2, 5)
            + (365 * $adjustedYear)
            + intdiv($adjustedYear, 4)
            - intdiv($adjustedYear, 100)
            + intdiv($adjustedYear, 400)
            - 32045;
    }

    private function julianDayToGregorian(int $julianDay): string
    {
        if (function_exists('jdtogregorian')) {
            return jdtogregorian($julianDay);
        }

        $adjustedJulianDay = $julianDay + 32044;
        $century = intdiv((4 * $adjustedJulianDay) + 3, 146097);
        $dayOfCentury = $adjustedJulianDay - intdiv(146097 * $century, 4);
        $yearOfCentury = intdiv((4 * $dayOfCentury) + 3, 1461);
        $dayOfYear = $dayOfCentury - intdiv(1461 * $yearOfCentury, 4);
        $monthIndex = intdiv((5 * $dayOfYear) + 2, 153);

        $day = $dayOfYear - intdiv((153 * $monthIndex) + 2, 5) + 1;
        $month = $monthIndex + 3 - (12 * intdiv($monthIndex, 10));
        $year = (100 * $century) + $yearOfCentury - 4800 + intdiv($monthIndex, 10);

        return $month.'/'.$day.'/'.$year;
    }
}
