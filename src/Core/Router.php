<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple pattern router supporting static and :param segments.
 */
final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: callable}> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $normalized = '/' . trim($pattern, '/');
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $normalized,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            return ($route['handler'])($request, $params);
        }

        return Response::html('<h1>404 - Pagina niet gevonden</h1>', 404);
    }

    /**
     * @return array<string, string>|null
     */
    private function match(string $pattern, string $path): ?array
    {
        if ($pattern === $path) {
            return [];
        }

        $patternParts = explode('/', trim($pattern, '/'));
        $pathParts = explode('/', trim($path, '/'));

        if (count($patternParts) !== count($pathParts)) {
            return null;
        }

        $params = [];
        foreach ($patternParts as $index => $segment) {
            $value = $pathParts[$index];
            if (str_starts_with($segment, ':')) {
                $paramName = substr($segment, 1);
                if ($value === '') {
                    return null;
                }
                $params[$paramName] = $value;
                continue;
            }

            if ($segment !== $value) {
                return null;
            }
        }

        return $params;
    }
}
