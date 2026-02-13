<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\HttpException;

/**
 * Simple router for handling HTTP routes
 */
class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /** @var array<callable> */
    private array $middlewares = [];

    public function use(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /** @var array{method: string, path: string}|null */
    private ?array $lastRoute = null;

    private function setRoute(string $method, string $path, array $handler): void
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => []
        ];

        $this->lastRoute = ['method' => $method, 'path' => $path];
    }

    public function get(string $path, array $handler): self
    {
        $this->setRoute('GET', $path, $handler);
        return $this;
    }

    public function post(string $path, array $handler): self
    {
        $this->setRoute('POST', $path, $handler);
        return $this;
    }

    public function addMiddleware(callable $middleware): self
    {
        if ($this->lastRoute) {
            $this->routes[$this->lastRoute['method']][$this->lastRoute['path']]['middlewares'][] = $middleware;
        }
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // global middlewares
        foreach ($this->middlewares as $middleware) {
            $response = $middleware($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        if (!isset($this->routes[$method])) {
            throw new HttpException($path, 404);
        }

        $matchedHandler = null;
        $matchedMiddlewares = [];
        $params = [];

        foreach ($this->routes[$method] as $routePath => $routeConfig) {
            $pattern = preg_quote($routePath, '#');
            $pattern = preg_replace('/\\\{([a-zA-Z0-9_]+)\\\}/', '([^/]+)', $pattern);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $matchedHandler = $routeConfig['handler'];
                $matchedMiddlewares = $routeConfig['middlewares'];
                $params = $matches;
                break;
            }
        }

        if (!$matchedHandler) {
            throw new HttpException($path, 404);
        }

        foreach ($matchedMiddlewares as $middleware) {
            $response = $middleware($request);
            if ($response instanceof Response) {
                return $response;
            }
        }

        [$class, $methodName] = $matchedHandler;
        return (new $class())->$methodName($request, ...$params);
    }
}
