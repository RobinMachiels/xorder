<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session management with flash messages and secure authentication state.
 */
final class Session
{
    public function start(string $cookiePath = '/'): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $cookiePath,
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => $isHttps,
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('xorder_session');
        session_start();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function userId(): ?int
    {
        $id = $_SESSION['user_id'] ?? null;
        return is_int($id) || (is_string($id) && ctype_digit($id)) ? (int) $id : null;
    }

    public function setUserId(int $userId): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $userId;
    }

    public function clearUser(): void
    {
        unset($_SESSION['user_id']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function flash(string $type, string $message): void
    {
        $flashes = $_SESSION['_flashes'] ?? [];
        if (!is_array($flashes)) {
            $flashes = [];
        }
        $flashes[] = ['type' => $type, 'message' => $message];
        $_SESSION['_flashes'] = $flashes;
    }

    /**
     * @return array<int, array{type: string, message: string}>
     */
    public function getFlashes(): array
    {
        $flashes = $_SESSION['_flashes'] ?? [];
        unset($_SESSION['_flashes']);

        return is_array($flashes) ? $flashes : [];
    }
}
