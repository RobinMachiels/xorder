<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP response emitter.
 */
final class Response
{
    /** Beveiligingsheaders op elk antwoord (vroeger in public/.htaccess). */
    private const SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; "
            . "script-src 'self'; connect-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'",
    ];

    private int $status;

    /** @var array<string, string> */
    private array $headers;

    private string $body;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $body = '', int $status = 200, array $headers = [])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($html, $status, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public static function redirect(string $location, int $status = 302): self
    {
        return new self('', $status, [
            'Location' => $location,
        ]);
    }

    /**
     * @param array<mixed> $data
     */
    public static function json(array $data, int $status = 200): self
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return new self($encoded === false ? '{}' : $encoded, $status, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    public static function download(string $binary, string $filename, string $mimeType): self
    {
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'export.xlsx';

        return new self($binary, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="' . $safeName . '"',
            'Content-Length' => (string) strlen($binary),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'public',
        ]);
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach (array_merge(self::SECURITY_HEADERS, $this->headers) as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        echo $this->body;
    }
}
