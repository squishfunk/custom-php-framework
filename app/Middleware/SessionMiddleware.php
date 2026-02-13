<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class SessionMiddleware
{
    public function __invoke(Request $request): ?Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return null;
    }
}
