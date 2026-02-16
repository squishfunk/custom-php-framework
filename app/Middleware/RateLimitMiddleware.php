<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $decayMinutes;
    private string $key;

    public function __construct(int $maxAttempts = 5, int $decayMinutes = 1, ?string $key = null)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->key = $key ?? 'rate_limit';
    }

    public function handle(Request $request, callable $next): Response
    {

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateKey = $this->key . '_' . $ip;

        $attempts = $_SESSION[$rateKey]['attempts'] ?? 0;
        $lastAttempt = $_SESSION[$rateKey]['last_attempt'] ?? 0;

        if (time() - $lastAttempt > $this->decayMinutes * 60) {
            $attempts = 0;
        }

        if ($attempts >= $this->maxAttempts) {
            $retryAfter = $this->decayMinutes * 60 - (time() - $lastAttempt);
            return new Response(
                'Too many attempts. Please try again in ' . ceil($retryAfter / 60) . ' minutes.',
                429,
                ['Retry-After' => $retryAfter]
            );
        }

        $_SESSION[$rateKey] = [
            'attempts' => $attempts + 1,
            'last_attempt' => time()
        ];

        $response = $next($request);
        
        // clear session on success
        if ($response->getStatusCode() < 400) {
            unset($_SESSION[$rateKey]);
        }

        return $response;
    }
}
