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

    private static function env(string $key, mixed $default = null): mixed
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

    public static function timezone(): string
    {
        return (string) self::env('APP_TIMEZONE');
    }

    public static function dbHost(): string
    {
        return (string) self::env('DB_HOST');
    }

    public static function dbPort(): int
    {
        return (int) self::env('DB_PORT');
    }

    public static function dbName(): string
    {
        return (string) self::env('DB_NAME');
    }

    public static function dbUser(): string
    {
        return (string) self::env('DB_USER');
    }

    public static function dbPass(): string
    {
        return (string) self::env('DB_PASS');
    }

    public static function dbCharset(): string
    {
        return (string) self::env('DB_CHARSET');
    }

    public static function bcbCdiDailyUrl(): string
    {
        return (string) self::env('BCB_CDI_DAILY_URL');
    }

    public static function bcbCdiAnnualUrl(): string
    {
        return (string) self::env('BCB_CDI_ANNUAL_URL');
    }

    public static function bcbSelicDailyUrl(): string
    {
        return (string) self::env('BCB_SELIC_DAILY_URL');
    }

    public static function bcbSelicMetaUrl(): string
    {
        return (string) self::env('BCB_SELIC_META_URL');
    }

    public static function defaultSelicMeta(): string
    {
        return (string) self::env('DEFAULT_SELIC_META', '14.25');
    }
}
