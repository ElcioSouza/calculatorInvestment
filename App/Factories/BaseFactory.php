<?php

namespace App\Factories;

use App\Console\ConsoleInput;

abstract class BaseFactory
{
    protected function normalizeDateOrFail(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false) {
            throw new \InvalidArgumentException('Data de aplicação inválida. Use o formato YYYY-MM-DD.');
        }

        $errors = \DateTimeImmutable::getLastErrors();
        if ($errors !== false && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
            throw new \InvalidArgumentException('Data de aplicação inválida. Use o formato YYYY-MM-DD.');
        }

        return $date->format('Y-m-d');
    }

    protected function askValidDate(string $message, string $default): string
    {
        while (true) {
            $value = trim(ConsoleInput::prompt($message, $default));

            try {
                return $this->normalizeDateOrFail($value);
            } catch (\InvalidArgumentException) {
                echo "Data inválida. Use o formato YYYY-MM-DD.\n";
            }
        }
    }

    protected function askValidBusinessDay(string $message, string $default): string
    {
        while (true) {
            $date = $this->askValidDate($message, $default);
            try {
                $this->ensureIsBusinessDay($date);
                return $date;
            } catch (\InvalidArgumentException $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }

    protected function askPositiveNumber(string $message, string $default, string $label): string
    {
        while (true) {
            $value = ConsoleInput::prompt($message, $default);

            try {
                return $this->normalizePositiveNumberOrFail($value, $label);
            } catch (\InvalidArgumentException) {
                echo $label . " inválido. Informe um número maior que zero.\n";
            }
        }
    }

    protected function askPositiveInteger(string $message, string $default, string $label): string
    {
        while (true) {
            $value = ConsoleInput::prompt($message, $default);

            try {
                return $this->normalizePositiveIntegerOrFail($value, $label);
            } catch (\InvalidArgumentException) {
                echo $label . " inválido. Informe um número inteiro maior que zero.\n";
            }
        }
    }

    protected function normalizePositiveNumberOrFail(string $value, string $label): string
    {
        $normalized = str_replace(',', '.', trim($value));

        if ($normalized === '' || !is_numeric($normalized)) {
            throw new \InvalidArgumentException($label . ' inválido. Informe um número maior que zero.');
        }

        if ((float) $normalized <= 0) {
            throw new \InvalidArgumentException($label . ' inválido. Informe um número maior que zero.');
        }

        return $normalized;
    }

    protected function normalizePositiveIntegerOrFail(string $value, string $label): string
    {
        $normalized = trim($value);

        if ($normalized === '' || !ctype_digit($normalized)) {
            throw new \InvalidArgumentException($label . ' inválido. Informe um número inteiro maior que zero.');
        }

        if ((int) $normalized <= 0) {
            throw new \InvalidArgumentException($label . ' inválido. Informe um número inteiro maior que zero.');
        }

        return $normalized;
    }

    private function getDayNamePt(\DateTimeImmutable $dt): string
    {
        return match ((int) $dt->format('N')) {
            1 => 'segunda-feira',
            2 => 'terça-feira',
            3 => 'quarta-feira',
            4 => 'quinta-feira',
            5 => 'sexta-feira',
            6 => 'sábado',
            7 => 'domingo',
        };
    }

    protected function nextBusinessDay(string $date): string
    {
        $dt = new \DateTimeImmutable($date, new \DateTimeZone('America/Sao_Paulo'));

        while ($this->isWeekendOrHoliday($dt)) {
            $dt = $dt->modify('+1 day');
        }

        return $dt->format('Y-m-d');
    }

    protected function ensureIsBusinessDay(string $date, string $label = 'Data de aplicação'): void
    {
        $dt = new \DateTimeImmutable($date, new \DateTimeZone('America/Sao_Paulo'));
        if ($this->isWeekendOrHoliday($dt)) {
            throw new \InvalidArgumentException(
                "{$label} deve ser um dia útil ({$dt->format('d/m/Y')} caiu em {$this->getDayNamePt($dt)} ou é feriado)."
            );
        }
    }

    protected function calculateRedemptionDateByMonths(string $applicationDate, int $months): string
    {
        $startDate = new \DateTimeImmutable($applicationDate);
        $targetMonthStart = $startDate->modify('first day of +' . $months . ' month');
        $day = (int) $startDate->format('j');
        $daysInMonth = (int) $targetMonthStart->format('t');
        $targetDay = min($day, $daysInMonth);

        $nominalDate = $targetMonthStart->setDate(
            (int) $targetMonthStart->format('Y'),
            (int) $targetMonthStart->format('m'),
            $targetDay
        );

        $redemption = $nominalDate;

        while ($this->isWeekendOrHoliday($redemption)) {
            $redemption = $redemption->modify('-1 day');
        }

        return $redemption->format('Y-m-d');
    }

    protected function isWeekendOrHoliday(\DateTimeImmutable $date): bool
    {
        if ((int) $date->format('N') >= 6) {
            return true;
        }

        $month = (int) $date->format('n');
        $day = (int) $date->format('j');
        $year = (int) $date->format('Y');

        foreach ([[1, 1], [4, 21], [5, 1], [9, 7], [10, 12], [11, 2], [11, 15], [11, 20], [12, 25]] as [$hM, $hD]) {
            if ($month === $hM && $day === $hD) {
                return true;
            }
        }

        $easter = $this->calculateEaster($year);
        $dateStr = $date->format('Y-m-d');

        foreach ([-47, -46, -2, 60] as $offset) {
            if ($easter->modify(($offset >= 0 ? '+' : '') . $offset . ' days')->format('Y-m-d') === $dateStr) {
                return true;
            }
        }

        return false;
    }

    protected function calculateEaster(int $year): \DateTimeImmutable
    {
        $a = $year % 19; $b = intdiv($year, 100); $c = $year % 100;
        $d = intdiv($b, 4); $e = $b % 4; $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4); $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable("{$year}-{$month}-{$day}");
    }
}