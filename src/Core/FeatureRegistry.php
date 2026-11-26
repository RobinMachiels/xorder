<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Finds the student feature pages: every features/<voornaam>.php becomes a page.
 * Names are lowercase letters, digits and hyphens only (also the asset folder name).
 */
final class FeatureRegistry
{
    public const NAME_PATTERN = '/^[a-z][a-z0-9-]{1,30}$/';

    /**
     * @return array<int, string>
     */
    public static function names(string $featuresDir): array
    {
        if (!is_dir($featuresDir)) {
            return [];
        }

        $names = [];
        foreach (glob($featuresDir . '/*.php') ?: [] as $file) {
            $name = basename($file, '.php');
            if (self::isValidName($name)) {
                $names[] = $name;
            }
        }
        sort($names);

        return $names;
    }

    public static function isValidName(string $name): bool
    {
        return preg_match(self::NAME_PATTERN, $name) === 1;
    }
}
