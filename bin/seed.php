<?php

declare(strict_types=1);

/**
 * CLI: laadt database/seed.sql (dummydata) in je lokale database.
 *
 * Gebruik:  php bin/seed.php
 *
 * - Maakt de tabellen aan als ze nog niet bestaan (zelfde Migrator als de app).
 * - Wist eerst alle bestaande rijen en zet daarna de testdata terug.
 * - Weigert te draaien als app.env = production.
 * - Werkt met SQLite (standaard na een clone) en met MySQL, zonder extra tools.
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

$root = dirname(__DIR__);
require_once $root . '/src/autoload.php';

$config = App\Core\Config::load($root);

if ($config->get('app.env') === 'production') {
    fwrite(STDERR, "Geweigerd: app.env staat op 'production'. Dummydata hoort enkel lokaal.\n");
    exit(1);
}

$seedFile = $root . '/database/seed.sql';
$sql = is_file($seedFile) ? file_get_contents($seedFile) : false;
if ($sql === false) {
    fwrite(STDERR, "Kan database/seed.sql niet lezen.\n");
    exit(1);
}

$db = new App\Core\Database($config);
(new App\Core\Migrator($db, $root))->ensureSchema();

// Commentaarregels weg, daarna splitsen op puntkomma (seed.sql bevat er geen in tekstwaarden).
$lines = array_filter(
    preg_split('/\R/', $sql) ?: [],
    static fn (string $line): bool => !str_starts_with(ltrim($line), '--')
);
$statements = array_filter(array_map('trim', explode(';', implode("\n", $lines))));

$pdo = $db->pdo();
$pdo->beginTransaction();
try {
    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Seed mislukt: ' . $e->getMessage() . "\n");
    exit(1);
}

$count = static fn (string $table): int => (int) ($db->fetchOne("SELECT COUNT(*) AS cnt FROM {$table}")['cnt'] ?? 0);

printf(
    "Dummydata geladen (%s): %d zaken, %d producten, %d bestellingen, %d bestelregels.\n",
    $db->driver(),
    $count('users'),
    $count('products'),
    $count('orders'),
    $count('order_items')
);
echo "Aanmelden kan met traiteur@voorbeeld.test, slager@voorbeeld.test of bakker@voorbeeld.test (wachtwoord: xorder123).\n";
