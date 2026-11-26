<?php

declare(strict_types=1);

/**
 * WordPress drop-in snippet voor assurio.be (mu-plugin of functions.php).
 * Laadt de tijdelijke "Kerstdorp" sticky knop enkel binnen de ingestelde feestperiode.
 */
add_action('wp_enqueue_scripts', static function (): void {
    $today = wp_date('Y-m-d');
    $startDate = '2026-10-15';
    $endDate = '2027-01-05';

    if ($today < $startDate || $today > $endDate) {
        return;
    }

    $baseWidgetUrl = 'https://xorder.assurio.be/widget';
    $targetLandingUrl = 'https://xorder.assurio.be/';

    wp_enqueue_style(
        'assurio-xorder-widget',
        $baseWidgetUrl . '/assurio-xorder-widget.css',
        [],
        '1.0.0'
    );

    wp_enqueue_script(
        'assurio-xorder-widget',
        $baseWidgetUrl . '/assurio-xorder-widget.js',
        [],
        '1.0.0',
        true
    );

    $inlineConfig = sprintf(
        'window.AssurioXOrderConfig = %s;',
        wp_json_encode([
            'startDate' => $startDate,
            'endDate' => $endDate,
            'targetUrl' => $targetLandingUrl,
            'lang' => 'auto',
            'position' => 'bottom-right',
            'dismissDays' => 14,
        ])
    );

    wp_add_inline_script('assurio-xorder-widget', $inlineConfig, 'before');
});
