<?php

declare(strict_types=1);

/**
 * Voorbeeldconfiguratie voor Combell shared hosting (MySQL).
 * Kopieer dit bestand naar config/config.php en vul jouw gegevens in.
 */
return [
    'app' => [
        'name' => 'XOrder by AssurIO',
        'version' => '1.0.0',
        'env' => 'production',
        'debug' => false,
        'timezone' => 'Europe/Brussels',
        'base_url' => '', // Laat leeg voor automatische detectie of zet bv. '/xorder'
        'default_locale' => 'nl',
        'supported_locales' => ['nl', 'fr'], // Voeg 'en' toe zodra lang/en.php bestaat
        'season_label' => 'Feestseizoen 2026',
        'mail_from' => 'noreply@jouwdomein.be', // Afzender voor wachtwoord-reset mails (bestaand mailadres op je Combell domein)
    ],
    'db' => [
        'driver' => 'mysql', // 'mysql' voor Combell, 'sqlite' voor snelle lokale test
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'IDxxxxxx_xorder',
        'username' => 'IDxxxxxx_xorder',
        'password' => 'VERANDER_DIT_WACHTWOORD',
        'charset' => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/../storage/xorder.sqlite',
    ],
    'widget' => [
        'start_date' => '2026-10-15',
        'end_date' => '2027-01-05',
        'position' => 'bottom-right',
        'dismiss_days' => 14,
    ],
];
