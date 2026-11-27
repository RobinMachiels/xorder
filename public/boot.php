<?php

declare(strict_types=1);

/**
 * Gedeelde start voor elk entry point in public/ (index.php, login.php, app/orders.php, ...).
 * Elk zichtbaar adres is een gewoon .php-bestand: geen .htaccess of rewrite nodig.
 */

// Rechtstreeks openen doet niets
if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    http_response_code(404);
    exit;
}

// Webroot (public/) voor routes en cache-busting van css/js, ook als de app-code elders staat
define('XORDER_PUBLIC_DIR', __DIR__);

// Standaard staat de app-code naast public/. Op een server waar enkel public/ in de webroot
// staat (bv. assurio.be/xorder), wijst public/xorder-path.php naar de map met de app-code.
$rootDir = dirname(__DIR__);
if (is_file(__DIR__ . '/xorder-path.php')) {
    $rootDir = (string) require __DIR__ . '/xorder-path.php';
}

require $rootDir . '/src/bootstrap.php';
