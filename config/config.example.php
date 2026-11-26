<?php

declare(strict_types=1);

/**
 * Lokale ontwikkelconfiguratie. Werkt meteen na een git clone (SQLite, geen installatie nodig).
 * Wil je iets aanpassen? Kopieer dit bestand naar config/config.php (staat in .gitignore).
 *
 * Voor de server: zie config/config.production.example.php.
 */
return [
    'app' => [
        'name' => 'XOrder by AssurIO',
        'version' => '1.1.0',
        'env' => 'local',
        'debug' => true,
        'timezone' => 'Europe/Brussels',
        'url' => '', // Volledige url zonder slash op het einde, bv. 'https://assurio.be/xorder'. Leeg = automatisch.
        'base_url' => '', // Leeg voor automatische detectie, of bv. '/xorder' bij een submap
        'default_locale' => 'nl',
        'supported_locales' => ['nl', 'fr'], // Voeg 'en' toe zodra lang/en.php bestaat
        'season_label' => 'Feestseizoen 2026',
        'mail_from' => 'noreply@localhost',
    ],
    'db' => [
        'driver' => 'sqlite',
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'xorder',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/../storage/xorder.sqlite',
    ],
    'features' => [
        // Pagina's van cursisten in features/<voornaam>.php. Lokaal aan, op productie uit.
        'enabled' => true,
    ],
    'widget' => [
        'start_date' => '2026-10-15',
        'end_date' => '2027-01-05',
        'position' => 'bottom-right',
        'dismiss_days' => 14,
    ],
];
