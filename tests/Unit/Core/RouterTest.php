<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\MiddlewareInterface;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Exception\HttpException;
use PHPUnit\Framework\TestCase;

class TestMiddleware implements MiddlewareInterface
{
    private ?Response $response;

    public function __construct(?Response $response = null)
    {
        $this->response = $response;
    }

    public function handle(Request $request, callable $next): Response
    {
        if ($this->response !== null) {
            return $this->response;
        }
        return $next($request);
    }
}

class TestController
{
    public function index(Request $request): Response
    {
        return new Response('index');
    }

    public function show(Request $request, string $id): Response
    {
        return new Response('show:' . $id);
    }

    public function store(Request $request): Response
    {
        return new Response('store');
    }
}


class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testGetRoute(): void
    {
        $result = $this->router->get('/test', [TestController::class, 'index']);

        $this->assertSame($this->router, $result);

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('index', $response->getContent());
    }

    public function testPostRoute(): void
    {
        $result = $this->router->post('/test', [TestController::class, 'store']);

        $this->assertSame($this->router, $result);
        $request = new Request('POST', '/test');
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('store', $response->getContent());
    }

    public function testRouteWithParameters(): void
    {
        $this->router->get('/users/{id}', [TestController::class, 'show']);

        $request = new Request('GET', '/users/123');
        $response = $this->router->dispatch($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('show:123', $response->getContent());
    }

    public function testRouteNotFound(): void
    {
        $this->router->get('/exists', [TestController::class, 'index']);

        $request = new Request('GET', '/not-exists');

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('/not-exists');
        $this->router->dispatch($request);
    }

    public function testMethodNotFound(): void
    {
        $this->router->get('/test', [TestController::class, 'index']);

        $request = new Request('POST', '/test');

        $this->expectException(HttpException::class);
        $this->router->dispatch($request);
    }

    public function testGlobalMiddleware(): void
    {
        $middlewareCalled = false;
        $middleware = new class($middlewareCalled) implements MiddlewareInterface {
            public function __construct(private &$called) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->called = true;
                return $next($request);
            }
        };

        $this->router->use($middleware);
        $this->router->get('/test', [TestController::class, 'index']);

        $request = new Request('GET', '/test');
        $this->router->dispatch($request);

        $this->assertTrue($middlewareCalled);
    }

    public function testGlobalMiddlewareReturnsResponse(): void
    {
        $middleware = new TestMiddleware(new Response('Intercepted', 403));

        $this->router->use($middleware);
        $this->router->get('/test', [TestController::class, 'index']);

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request);

        $this->assertEquals('Intercepted', $response->getContent());
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRouteSpecificMiddleware(): void
    {
        $middlewareCalled = false;
        $middleware = new class($middlewareCalled) implements MiddlewareInterface {
            public function __construct(private &$called) {}
            public function handle(Request $request, callable $next): Response
            {
                $this->called = true;
                return $next($request);
            }
        };

        $this->router
            ->get('/test', [TestController::class, 'index'])
            ->addMiddleware($middleware);

        $request = new Request('GET', '/test');
        $this->router->dispatch($request);

        $this->assertTrue($middlewareCalled);
    }

    public function testRouteSpecificMiddlewareReturnsResponse(): void
    {
        $middleware = new TestMiddleware(new Response('Route blocked', 401));

        $this->router
            ->get('/test', [TestController::class, 'index'])
            ->addMiddleware($middleware);

        $request = new Request('GET', '/test');
        $response = $this->router->dispatch($request);

        $this->assertEquals('Route blocked', $response->getContent());
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testMultipleRoutes(): void
    {
        $this->router->get('/users', [TestController::class, 'index']);
        $this->router->get('/users/{id}', [TestController::class, 'show']);
        $this->router->post('/users', [TestController::class, 'store']);

        $request1 = new Request('GET', '/users');
        $response1 = $this->router->dispatch($request1);
        $this->assertEquals('index', $response1->getContent());

        $request2 = new Request('GET', '/users/456');
        $response2 = $this->router->dispatch($request2);
        $this->assertEquals('show:456', $response2->getContent());

        $request3 = new Request('POST', '/users');
        $response3 = $this->router->dispatch($request3);
        $this->assertEquals('store', $response3->getContent());
    }

    public function testComplexRouteParameter(): void
    {
        $handler = [TestController::class, 'show'];
        $this->router->get('/api/v1/resources/{resourceId}', $handler);

        $request = new Request('GET', '/api/v1/resources/abc-123_test');
        $response = $this->router->dispatch($request);

        $this->assertEquals('show:abc-123_test', $response->getContent());
    }
}