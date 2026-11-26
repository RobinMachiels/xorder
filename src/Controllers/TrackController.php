<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * Anonymous counters for the seasonal sticky button (no IP, no cookies, no personal data).
 */
final class TrackController extends BaseController
{
    private const EVENTS = ['impression', 'expand', 'click', 'dismiss'];

    public function track(Request $request): Response
    {
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Cache-Control' => 'no-store',
        ];

        $raw = (string) file_get_contents('php://input', false, null, 0, 1024);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return new Response('', 204, $headers);
        }

        $event = (string) ($data['event'] ?? '');
        if (!in_array($event, self::EVENTS, true)) {
            return new Response('', 204, $headers);
        }

        $lang = (string) ($data['lang'] ?? 'nl');
        if (!in_array($lang, ['nl', 'fr', 'en'], true)) {
            $lang = 'nl';
        }

        $host = strtolower((string) preg_replace('/[^a-zA-Z0-9.\-:]/', '', (string) ($data['host'] ?? '')));
        $host = substr($host, 0, 120);

        $this->app->db->execute(
            'INSERT INTO widget_events (event, lang, host, created_at) VALUES (?, ?, ?, ?)',
            [$event, $lang, $host, date('Y-m-d H:i:s')]
        );

        return new Response('', 204, $headers);
    }
}
