<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request abstraction with dynamic base-path handling for root or subfolder installs.
 */
final class Request
{
    private string $method;
    private string $path;
    private string $basePath;

    /** @var array<string, mixed> */
    private array $query;

    /** @var array<string, mixed> */
    private array $post;

    /** @var array<string, mixed> */
    private array $server;

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     */
    public function __construct(
        string $method,
        string $path,
        string $basePath,
        array $query,
        array $post,
        array $server
    ) {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->basePath = $basePath;
        $this->query = $query;
        $this->post = $post;
        $this->server = $server;
    }

    public static function capture(string $configuredBaseUrl = ''): self
    {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rawUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $parsedPath = parse_url($rawUri, PHP_URL_PATH);
        $uriPath = is_string($parsedPath) ? $parsedPath : '/';

        $basePath = self::detectBasePath($configuredBaseUrl, $_SERVER);

        $path = $uriPath;
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
        $path = '/' . ltrim((string) $path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        return new self($method, $path, $basePath, $_GET, $_POST, $_SERVER);
    }

    /**
     * @param array<string, mixed> $server
     */
    private static function detectBasePath(string $configuredBaseUrl, array $server): string
    {
        if ($configuredBaseUrl !== '') {
            return rtrim($configuredBaseUrl, '/');
        }

        $scriptName = (string) ($server['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($scriptName));

        if ($dir === '/' || $dir === '.' || $dir === '\\') {
            return '';
        }

        if (str_ends_with($dir, '/public')) {
            $parent = substr($dir, 0, -strlen('/public'));
            $requestUri = (string) ($server['REQUEST_URI'] ?? '');
            if (!str_starts_with($requestUri, $dir)) {
                return $parent === '/' ? '' : rtrim($parent, '/');
            }
        }

        return rtrim($dir, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function path(): string
    {
        return $this->path;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function url(string $path = '/'): string
    {
        $normalized = '/' . ltrim($path, '/');
        if ($normalized === '/') {
            return $this->basePath === '' ? '/' : $this->basePath . '/';
        }

        return $this->basePath . $normalized;
    }

    public function absoluteUrl(string $path = '/'): string
    {
        $https = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ((int) ($this->server['SERVER_PORT'] ?? 80) === 443);
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($this->server['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host . $this->url($path);
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allPost(): array
    {
        return $this->post;
    }

    public function acceptLanguage(): string
    {
        return (string) ($this->server['HTTP_ACCEPT_LANGUAGE'] ?? '');
    }
}
