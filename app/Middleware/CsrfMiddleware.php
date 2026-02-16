<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\CsrfToken;
use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    private array $excludedRoutes = [];

    public function __construct(array $excludedRoutes = [])
    {
        $this->excludedRoutes = $excludedRoutes;
    }

    public function handle(Request $request, callable $next): Response
    {
        $method = $request->getMethod();

        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return $next($request);
        }

        $path = $request->getPath();
        foreach ($this->excludedRoutes as $excludedRoute) {
            if (strpos($path, $excludedRoute) === 0) {
                return $next($request);
            }
        }

        $token = $request->input('_token') ?? ($request->getPost()['_token'] ?? null);

        if (!CsrfToken::validate($token)) {
            return new Response('Invalid CSRF token', 403);
        }

        return $next($request);
    }
}
