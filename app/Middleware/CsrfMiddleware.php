<?php

namespace App\Middleware;

use App\Core\CsrfToken;
use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware
{
    private array $excludedRoutes = [];

    public function __construct(array $excludedRoutes = [])
    {
        $this->excludedRoutes = $excludedRoutes;
    }

    public function __invoke(Request $request): ?Response
    {
        $method = $request->getMethod();

        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return null;
        }

        $path = $request->getPath();
        foreach ($this->excludedRoutes as $excludedRoute) {
            if (strpos($path, $excludedRoute) === 0) {
                return null;
            }
        }

        $token = $request->input('_token') ?? ($request->getPost()['_token'] ?? null);

        if (!CsrfToken::validate($token)) {
            return new Response('Invalid CSRF token', 403);
        }

        return null;
    }
}
