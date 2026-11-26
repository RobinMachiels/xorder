<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight file-based internationalization service (NL/FR built-in, EN-ready).
 */
final class I18n
{
    private string $cookiePath = '/';

    private string $langDir;
    private string $defaultLocale;

    /** @var array<int, string> */
    private array $supportedLocales;

    private string $locale;

    /** @var array<string, string> */
    private array $messages = [];

    /** @var array<string, string> */
    private array $fallbackMessages = [];

    /**
     * @param array<int, string> $supportedLocales
     */
    public function __construct(string $langDir, string $defaultLocale, array $supportedLocales)
    {
        $this->langDir = rtrim($langDir, '/');
        $this->defaultLocale = $defaultLocale;
        $this->supportedLocales = $supportedLocales;
        $this->locale = $defaultLocale;
        $this->fallbackMessages = $this->loadFile($defaultLocale);
        $this->messages = $this->fallbackMessages;
    }

    public function resolve(Request $request, Session $session, ?string $userLocale = null): void
    {
        $requested = $request->query('lang');
        if (is_string($requested) && $this->isSupported($requested)) {
            $this->setLocale($requested, $session);
            return;
        }

        $sessionLocale = $session->get('locale');
        if (is_string($sessionLocale) && $this->isSupported($sessionLocale)) {
            $this->setLocale($sessionLocale, $session);
            return;
        }

        if ($userLocale !== null && $this->isSupported($userLocale)) {
            $this->setLocale($userLocale, $session);
            return;
        }

        $cookieLocale = $_COOKIE['xorder_lang'] ?? null;
        if (is_string($cookieLocale) && $this->isSupported($cookieLocale)) {
            $this->setLocale($cookieLocale, $session);
            return;
        }

        $accept = strtolower($request->acceptLanguage());
        foreach ($this->supportedLocales as $supported) {
            if ($accept !== '' && str_contains($accept, strtolower($supported))) {
                $this->setLocale($supported, $session);
                return;
            }
        }

        $this->setLocale($this->defaultLocale, $session);
    }

    public function setCookiePath(string $path): void
    {
        $this->cookiePath = $path;
    }

    public function setLocale(string $locale, ?Session $session = null): void
    {
        if (!$this->isSupported($locale)) {
            $locale = $this->defaultLocale;
        }

        $this->locale = $locale;
        $this->messages = $this->loadFile($locale);

        if ($session !== null) {
            $session->set('locale', $locale);
        }

        if (!headers_sent()) {
            setcookie('xorder_lang', $locale, [
                'expires' => time() + (86400 * 180),
                'path' => $this->cookiePath,
                'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'samesite' => 'Lax',
            ]);
        }
    }

    public function locale(): string
    {
        return $this->locale;
    }

    /**
     * @return array<int, string>
     */
    public function supportedLocales(): array
    {
        return $this->supportedLocales;
    }

    public function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales, true)
            && is_file($this->langDir . '/' . $locale . '.php');
    }

    /**
     * @param array<string, string|int|float> $params
     */
    public function t(string $key, array $params = []): string
    {
        $text = $this->messages[$key] ?? $this->fallbackMessages[$key] ?? $key;

        if ($params === []) {
            return $text;
        }

        $replacements = [];
        foreach ($params as $paramKey => $value) {
            $replacements['{' . $paramKey . '}'] = (string) $value;
        }

        return strtr($text, $replacements);
    }

    /**
     * @return array<string, string>
     */
    private function loadFile(string $locale): array
    {
        $path = $this->langDir . '/' . $locale . '.php';
        if (!is_file($path)) {
            return [];
        }

        $loaded = require $path;
        return is_array($loaded) ? $loaded : [];
    }
}
