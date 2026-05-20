<?php

namespace App\Services;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use App\Contracts\CountsBusinessDaysInterface;

class BusinessDayService extends ServiceBase implements CountsBusinessDaysInterface
{
    public function __construct(
        private array $fixedHolidays = self::FIXED_HOLIDAYS,
        private array $easterOffsets = self::EASTER_OFFSETS
    ) {}

    public function isBusinessDay(string $date): bool
    {
        $d       = new DateTimeImmutable($date);
        $weekday = (int) $d->format('N');

        if ($weekday >= 6) {
            return false;
        }

        $year     = (int) $d->format('Y');
        $holidays = array_flip($this->getHolidaysForYear($year));
        $dateKey = $d->format('Y-m-d');
        return !isset($holidays[$dateKey]);
    }


    public function countBusinessDays(string $startDate, string $endDate): int
    {
        $start = new DateTimeImmutable($startDate);
        $end   = new DateTimeImmutable($endDate);

        if ($end <= $start) {
            return 0;
        }

        $holidayMap = [];
        $startYear  = (int)$start->format('Y');
        $endYear    = (int)$end->format('Y');

        for ($year = $startYear; $year <= $endYear; $year++) {
            foreach ($this->getHolidaysForYear($year) as $holiday) {
                $holidayMap[$holiday] = true;
            }
        }

        $days   = 0;
        $period = new DatePeriod($start, new DateInterval('P1D'), $end);

        foreach ($period as $date) {
            $weekday = (int)$date->format('N');
            $dateKey = $date->format('Y-m-d');

            if ($weekday < 6 && !isset($holidayMap[$dateKey])) {
                $days++;
            }
        }

        return $days;
    }

    private function getHolidaysForYear(int $year): array
    {
        $easter    = $this->calculateEasterDate($year);
        $holidays  = [];

        foreach ($this->fixedHolidays as $monthDay) {
            $holidays[] = sprintf('%04d-%s', $year, $monthDay);
        }

        foreach ($this->easterOffsets as $offset) {
            $holidays[] = $easter
                ->modify(($offset >= 0 ? '+' : '') . $offset . ' days')
                ->format('Y-m-d');
        }

        return $holidays;
    }

    private function calculateEasterDate(int $year): DateTimeImmutable
    {
        $a = $year % 19; 
        $b = intdiv($year, 100);
        $c = $year % 100; 
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30; 
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7; 
        $m = intdiv($a + 11 * $h + 22 * $l, 451); 

        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day   = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
    }
}