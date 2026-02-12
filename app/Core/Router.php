<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple router for handling HTTP routes
 */
class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /**
     * Register a GET route
     *
     * @param string $path Route path
     * @param array $handler Route handler
     */
    public function get(string $path, array $handler): self
    {
        $this->routes['GET'][$path] = $handler;
        return $this;
    }

    /**
     * Register a POST route
     *
     * @param string $path Route path
     * @param array $handler Route handler
     */
    public function post(string $path, array $handler): self
    {
        $this->routes['POST'][$path] = $handler;
        return $this;
    }

    /**
     * Dispatch the request to the appropriate handler
     *
     * @param Request $request The HTTP request
     * @return Response The HTTP response
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        if (!isset($this->routes[$method][$path])) {
            return new Response(
                '404 Not Found',
                404
            );
        }

        [$class, $method] = $this->routes[$method][$path];

        return (new $class())->$method($request);
    }
}
