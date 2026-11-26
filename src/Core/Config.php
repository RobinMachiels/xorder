<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Application configuration loader and accessor.
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $items;

    /**
     * @param array<string, mixed> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function load(string $rootDir): self
    {
        $localPath = $rootDir . '/config/config.php';
        $examplePath = $rootDir . '/config/config.example.php';

        $path = is_file($localPath) ? $localPath : $examplePath;
        $data = is_file($path) ? require $path : [];

        if (!is_array($data)) {
            $data = [];
        }

        $timezone = (string) ($data['app']['timezone'] ?? 'Europe/Brussels');
        date_default_timezone_set($timezone);

        return new self($data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function isDebug(): bool
    {
        return (bool) $this->get('app.debug', false);
    }
}
