<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Koppelt een route (het .php-bestand in public/, zonder extensie) en HTTP-methode aan een handler.
 * Parameters komen uit de querystring, bv. /app/order-edit.php?id=5 geeft ['id' => '5'].
 */
final class Router
{
    /** @var array<string, array{handler: callable, params: array<int, string>}> */
    private array $routes = [];

    /**
     * @param array<int, string> $params Verplichte queryparameters die de handler in $params krijgt
     */
    public function get(string $route, callable $handler, array $params = []): void
    {
        $this->add('GET', $route, $handler, $params);
    }

    /**
     * @param array<int, string> $params Verplichte queryparameters die de handler in $params krijgt
     */
    public function post(string $route, callable $handler, array $params = []): void
    {
        $this->add('POST', $route, $handler, $params);
    }

    /**
     * @param array<int, string> $params
     */
    private function add(string $method, string $route, callable $handler, array $params): void
    {
        $this->routes[$method . ' ' . self::normalize($route)] = [
            'handler' => $handler,
            'params' => $params,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->routes[$request->method() . ' ' . self::normalize($request->path())] ?? null;
        if ($route === null) {
            return self::notFound();
        }

        $params = [];
        foreach ($route['params'] as $name) {
            $value = $request->query($name);
            if (!is_string($value) || $value === '') {
                return self::notFound();
            }
            $params[$name] = $value;
        }

        return ($route['handler'])($request, $params);
    }

    private static function normalize(string $route): string
    {
        return '/' . trim($route, '/');
    }

    private static function notFound(): Response
    {
        return Response::html('<h1>404 - Pagina niet gevonden</h1>', 404);
    }
}
