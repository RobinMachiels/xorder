<?php

declare(strict_types=1);

namespace App\Core;

/**
 * HTTP request abstraction. De route volgt uit het .php-bestand dat de server uitvoert,
 * de basis-url uit SCRIPT_NAME, zodat de app werkt in de root of in een submap zonder rewrites.
 */
final class Request
{
    /** Routes die een map met index.php zijn: '/app' is public/app/index.php. */
    private const DIRECTORY_ROUTES = ['/app'];

    private string $method;
    private string $path;
    private string $basePath;
    private string $appUrl = '';

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

    public static function capture(string $configuredBaseUrl = '', string $appUrl = ''): self
    {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $script = self::scriptInPublic($_SERVER);

        $basePath = $configuredBaseUrl !== ''
            ? rtrim($configuredBaseUrl, '/')
            : self::detectBasePath($script, $_SERVER);

        $request = new self($method, self::routeFromScript($script), $basePath, $_GET, $_POST, $_SERVER);
        $request->appUrl = rtrim($appUrl, '/');

        return $request;
    }

    /**
     * Pad van het uitgevoerde bestand binnen public/, bv. '/app/orders.php'.
     * Elk entry point is een gewoon .php-bestand, dus er is geen rewrite (.htaccess) nodig.
     *
     * @param array<string, mixed> $server
     */
    private static function scriptInPublic(array $server): string
    {
        $scriptName = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? '/index.php'));

        $publicDir = defined('XORDER_PUBLIC_DIR') ? realpath((string) XORDER_PUBLIC_DIR) : false;
        $scriptFile = realpath((string) ($server['SCRIPT_FILENAME'] ?? ''));
        if ($publicDir !== false && $scriptFile !== false && str_starts_with($scriptFile, $publicDir . DIRECTORY_SEPARATOR)) {
            return str_replace('\\', '/', substr($scriptFile, strlen($publicDir)));
        }

        return '/' . basename($scriptName);
    }

    /**
     * '/app/orders.php' wordt de route '/app/orders', '/app/index.php' wordt '/app', '/index.php' wordt '/'.
     */
    private static function routeFromScript(string $script): string
    {
        $route = preg_replace('/\.php$/', '', $script) ?? $script;
        $route = preg_replace('#(^|/)index$#', '', $route) ?? $route;

        return '/' . trim($route, '/');
    }

    /**
     * De basis-url is SCRIPT_NAME zonder het pad binnen public/, bv. '/xorder/app/orders.php' wordt '/xorder'.
     *
     * @param array<string, mixed> $server
     */
    private static function detectBasePath(string $script, array $server): string
    {
        $scriptName = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? ''));
        $base = str_ends_with($scriptName, $script)
            ? substr($scriptName, 0, -strlen($script))
            : rtrim(str_replace('\\', '/', dirname($scriptName)), '/.');

        // Volledig project in de webroot met de optionele rewrite naar public/ (zie /.htaccess)
        if (str_ends_with($base, '/public')) {
            $requestUri = (string) ($server['REQUEST_URI'] ?? '');
            if (!str_starts_with($requestUri, $base . '/')) {
                $base = substr($base, 0, -strlen('/public'));
            }
        }

        return rtrim($base, '/');
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

    /**
     * Link naar een route of een bestand in public/.
     * '/login' wordt '<base>/login.php', '/app' wordt '<base>/app/', '/' blijft '<base>/'.
     * Paden met een extensie (css, js, afbeeldingen) blijven ongewijzigd. Een #anker mag.
     *
     * @param array<string, scalar|null> $query
     */
    public function url(string $path = '/', array $query = []): string
    {
        return $this->basePath . self::routeToFile($path, $query);
    }

    /**
     * @param array<string, scalar|null> $query
     */
    public function absoluteUrl(string $path = '/', array $query = []): string
    {
        if ($this->appUrl !== '') {
            return $this->appUrl . self::routeToFile($path, $query);
        }

        $https = (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || ((int) ($this->server['SERVER_PORT'] ?? 80) === 443);
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($this->server['HTTP_HOST'] ?? 'localhost');

        return $scheme . '://' . $host . $this->url($path, $query);
    }

    /**
     * @param array<string, scalar|null> $query
     */
    private static function routeToFile(string $path, array $query): string
    {
        $fragment = '';
        $hashPos = strpos($path, '#');
        if ($hashPos !== false) {
            $fragment = substr($path, $hashPos);
            $path = substr($path, 0, $hashPos);
        }

        $path = '/' . trim($path, '/');
        $lastSegment = basename($path);

        if ($path === '/') {
            $file = '/';
        } elseif (str_contains($lastSegment, '.')) {
            $file = $path;
        } elseif (in_array($path, self::DIRECTORY_ROUTES, true)) {
            $file = $path . '/';
        } else {
            $file = $path . '.php';
        }

        $query = array_filter($query, static fn ($value): bool => $value !== null && $value !== '');
        $queryString = $query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        return $file . $queryString . $fragment;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function allQuery(): array
    {
        return $this->query;
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

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function acceptLanguage(): string
    {
        return (string) ($this->server['HTTP_ACCEPT_LANGUAGE'] ?? '');
    }
}
