<?php

declare(strict_types=1);

/**
 * Productieconfiguratie (assurio.be/xorder op Combell).
 * Op de server opslaan als config/config.php in de app-map (buiten de webroot).
 * Nooit echte wachtwoorden in git zetten.
 */
return [
    'app' => [
        'name' => 'XOrder by AssurIO',
        'version' => '1.1.0',
        'env' => 'production',
        'debug' => false, // Nooit true op productie: dan toont de reset-pagina de resetlink
        'timezone' => 'Europe/Brussels',
        'url' => 'https://assurio.be/xorder', // Gebruikt voor links in mails (nooit de Host-header vertrouwen)
        'base_url' => '/xorder',
        'default_locale' => 'nl',
        'supported_locales' => ['nl', 'fr'],
        'season_label' => 'Feestseizoen 2026',
        'mail_from' => 'noreply@assurio.be',
    ],
    'db' => [
        'driver' => 'mysql',
        'host' => 'IDxxxxxx_xorder.db.webhosting.be',
        'port' => 3306,
        'database' => 'IDxxxxxx_xorder',
        'username' => 'IDxxxxxx_xorder',
        'password' => 'VERANDER_DIT_WACHTWOORD',
        'charset' => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/../storage/xorder.sqlite',
    ],
    'features' => [
        'enabled' => false,
    ],
    'widget' => [
        'start_date' => '2026-10-15',
        'end_date' => '2027-01-05',
        'position' => 'bottom-right',
        'dismiss_days' => 14,
    ],
];
