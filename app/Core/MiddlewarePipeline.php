<?php

declare(strict_types=1);

namespace App\Core;

class MiddlewarePipeline
{
    /** @var array<MiddlewareInterface> */
    private array $middlewares = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function handle(Request $request, callable $handler): Response
    {
        // create controller handler
        $next = function (Request $request) use ($handler): Response {
            return $handler($request);
        };

        // reverse order to execute middleware in order
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function (Request $request) use ($middleware, $next): Response {
                return $middleware->handle($request, $next);
            };
        }

        return $next($request);
    }
}
