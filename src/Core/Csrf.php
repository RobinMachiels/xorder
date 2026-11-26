<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-session CSRF protection helper.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function token(): string
    {
        $existing = $this->session->get(self::SESSION_KEY);
        if (is_string($existing) && strlen($existing) >= 32) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $this->session->set(self::SESSION_KEY, $token);
        return $token;
    }

    public function field(): string
    {
        $escaped = htmlspecialchars($this->token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="_csrf" value="' . $escaped . '">';
    }

    public function verify(?string $submittedToken): bool
    {
        if (!is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        $expected = $this->token();
        return hash_equals($expected, $submittedToken);
    }
}
