<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\HttpException;

class Router
{
    /** @var array<string, array<string, array{handler: array, middlewares: array<MiddlewareInterface>}>> */
    private array $routes = [];

    /** @var array<MiddlewareInterface> */
    private array $globalMiddlewares = [];

    /** @var array{method: string, path: string}|null */
    private ?array $lastRoute = null;

    public function use(MiddlewareInterface $middleware): void
    {
        $this->globalMiddlewares[] = $middleware;
    }

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

    public function addMiddleware(MiddlewareInterface $middleware): self
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

        if (!isset($this->routes[$method])) {
            throw new HttpException($path, 404);
        }

        $matchedHandler = null;
        $routeMiddlewares = [];
        $params = [];

        foreach ($this->routes[$method] as $routePath => $routeConfig) {
            $pattern = preg_quote($routePath, '#');
            $pattern = preg_replace('/\\\{([a-zA-Z0-9_]+)\\\}/', '([^/]+)', $pattern);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches);
                $matchedHandler = $routeConfig['handler'];
                $routeMiddlewares = $routeConfig['middlewares'];
                $params = $matches;
                break;
            }
        }

        if (!$matchedHandler) {
            throw new HttpException($path, 404);
        }

        $pipeline = new MiddlewarePipeline();

        foreach ($this->globalMiddlewares as $middleware) {
            $pipeline->add($middleware);
        }

        foreach ($routeMiddlewares as $middleware) {
            $pipeline->add($middleware);
        }

        [$class, $methodName] = $matchedHandler;

        return $pipeline->handle($request, function (Request $request) use ($class, $methodName, $params): Response {
            return (new $class())->$methodName($request, ...$params);
        });
    }
}
