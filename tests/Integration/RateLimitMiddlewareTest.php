<?php

declare(strict_types=1);

namespace Tests\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Middleware\RateLimitMiddleware;
use PHPUnit\Framework\TestCase;

class RateLimitMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testAllowsRequestWithinLimit(): void
    {
        $middleware = new RateLimitMiddleware(5, 1, 'test');
        $request = new Request('POST', '/test');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBlocksRequestAfterMaxAttempts(): void
    {
        $middleware = new RateLimitMiddleware(2, 1, 'test');
        $request = new Request('POST', '/test');

        $middleware->handle($request, function ($req) {
            return new Response('Invalid credentials', 401);
        });

        $middleware->handle($request, function ($req) {
            return new Response('Invalid credentials', 401);
        });

        $response = $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertStringContainsString('Too many attempts', $response->getContent());
    }

    public function testSuccessfulRequestClearsAttempts(): void
    {
        $middleware = new RateLimitMiddleware(5, 1, 'test');
        $request = new Request('POST', '/test');

        $middleware->handle($request, function ($req) {
            return new Response('Success', 200);
        });

        $this->assertEmpty($_SESSION);
    }

    public function testFailedRequestRetainsAttempts(): void
    {
        $middleware = new RateLimitMiddleware(5, 1, 'test');
        $request = new Request('POST', '/test');

        $middleware->handle($request, function ($req) {
            return new Response('Unauthorized', 401);
        });

        $this->assertNotEmpty($_SESSION);
        $ip = $_SERVER['REMOTE_ADDR'];
        $this->assertEquals(1, $_SESSION['test_' . $ip]['attempts']);
    }
}
