<?php

namespace App\Console;

final class ConsoleInput
{
    public static function isInteractive(): bool
    {
        return function_exists('stream_isatty') ? stream_isatty(STDIN) : true;
    }

    public static function option(array $argv, string $name, string $default = ''): string
    {
        $long = '--' . $name;

        foreach ($argv as $index => $value) {
            if ($value === $long && isset($argv[$index + 1])) {
                return (string) $argv[$index + 1];
            }

            if (str_starts_with($value, $long . '=')) {
                return (string) substr($value, strlen($long) + 1);
            }
        }

        return $default;
    }

    public static function prompt(string $message, string $default = ''): string
    {
        if (!defined('STDIN') || !self::isInteractive()) {
            return $default;
        }

        echo $message;
        $input = fgets(STDIN);

        if ($input === false) {
            return $default;
        }

        $input = trim($input);

        return $input === '' ? $default : $input;
    }


    public static function askOption(string $message, array $allowed, string $default): string
    {
        while (true) {
            $value = strtolower(trim(self::prompt($message, $default)));

            if (in_array($value, $allowed, true)) {
                return $value;
            }

            echo "Opção inválida. Tente novamente.\n";
        }
    }

    public static function normalizeRateType(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            '1', 'pre', 'pré' => 'pre',
            '2', 'pos', 'pós', 'post' => 'pos',
            default => $value,
        };
    }

    public static function normalizeInvestmentType(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            '1', 'cdb' => 'cdb',
            '2', 'lci' => 'lci',
            '3', 'lca' => 'lca',
            default => $value,
        };
    }

    public static function showInvestmentDefaults(string $selicDefault = '14.40'): void
    {
        if (self::isInteractive()) {
            $defaultDate = (new \DateTime())->format('Y-m-d');

            echo "Padrões:\n";
            echo "Investimento [1=CDB 2=LCI 3=LCA] (padrão: 1)\n";
            echo "Taxa [1=pré 2=pós] (padrão: 2)\n";
            echo "Data de aplicação [{$defaultDate}]\n";
            echo "Prazo de investimento (meses de calendário) [1]\n";
            echo "Capital inicial [10000]\n";
            echo "Rentabilidade (% do CDI) [100]\n";
            echo "Selic Meta [{$selicDefault}]\n\n";
        }
    }
}