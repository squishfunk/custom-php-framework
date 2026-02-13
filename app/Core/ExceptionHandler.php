<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\RouteNotFoundException;
use Throwable;

class ExceptionHandler
{
    public function handle(Throwable $e): Response
    {
        if ($e instanceof RouteNotFoundException) {
            return new Response(
                "404 Not Found: " . $e->getMessage(),
                404
            );
        }

        return new Response(
            "500 Internal Server Error: " . $e->getMessage(),
            500
        );
    }
}
