<?php
namespace App\Core;

class Config
{
    private static array $cache = [];
    private static bool $loaded = false;

    public static function load(?string $envDir = null): void
    {
        if (self::$loaded) {
            return;
        }

        $envDir ??= dirname(__DIR__, 2);

        $envFile = $envDir . '/.env';

        if (!file_exists($envFile)) {
            self::$loaded = true;
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            self::$loaded = true;
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            $key = trim($key);
            $value = trim($value);

            self::$cache[$key] = $value;

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $value = getenv($key);
        if ($value !== false) {
            self::$cache[$key] = $value;
            return $value;
        }

        return $default;
    }

    public static function getString(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);
        return (string) $value;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);
        return (int) $value;
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = self::get($key, $default);
        return (float) $value;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default ? 'true' : 'false');

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
