<?php

declare(strict_types=1);

/**
 * CLI: toont de anonieme statistieken van de AssurIO seizoensknop.
 * Gebruik: php bin/widget-stats.php
 */
if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

require_once dirname(__DIR__) . '/src/autoload.php';

$config = App\Core\Config::load(dirname(__DIR__));
$db = new App\Core\Database($config);

$rows = $db->fetchAll(
    'SELECT substr(created_at, 1, 10) AS day, event, COUNT(*) AS cnt
     FROM widget_events GROUP BY day, event ORDER BY day DESC, event ASC'
);

if ($rows === []) {
    echo "Nog geen events geregistreerd.\n";
    exit(0);
}

$byDay = [];
foreach ($rows as $row) {
    $byDay[$row['day']][$row['event']] = (int) $row['cnt'];
}

printf("%-12s %11s %8s %7s %8s %6s\n", 'Dag', 'Impressions', 'Expand', 'Click', 'Dismiss', 'CTR');
foreach ($byDay as $day => $counts) {
    $imp = $counts['impression'] ?? 0;
    $click = $counts['click'] ?? 0;
    printf(
        "%-12s %11d %8d %7d %8d %5.1f%%\n",
        $day,
        $imp,
        $counts['expand'] ?? 0,
        $click,
        $counts['dismiss'] ?? 0,
        $imp > 0 ? ($click / $imp) * 100 : 0.0
    );
}
