<?php

declare(strict_types=1);

namespace App\Core\Exception;

use Exception;

class RouteNotFoundException extends Exception
{
    public function __construct(string $path = "", int $code = 404, ?\Throwable $previous = null)
    {
        $message = "Route not found: {$path}";
        parent::__construct($message, $code, $previous);
    }
}
