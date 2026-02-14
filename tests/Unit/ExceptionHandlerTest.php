<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\ExceptionHandler;
use App\Core\Exception\HttpException;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['APP_ENV']);
    }

    public function testRenderDebugInDevEnvironment()
    {
        $_ENV['APP_ENV'] = 'dev';

        $handler = new ExceptionHandler();
        $exception = new \Exception('Test Debug Exception');

        $response = $handler->handle($exception);

        $this->assertStringContainsString('Test Debug Exception', $response->getContent());
        $this->assertStringContainsString('Stack Trace', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testRenderGenericInProductionEnvironment()
    {
        $_ENV['APP_ENV'] = 'prod';

        $handler = new ExceptionHandler();
        $exception = new \Exception('Test Production Exception');

        $response = $handler->handle($exception);

        $this->assertStringNotContainsString('Stack Trace', $response->getContent());
        $this->assertStringContainsString('Something went wrong', $response->getContent());
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testHttpExceptionInProduction()
    {
        $_ENV['APP_ENV'] = 'prod';

        $handler = new ExceptionHandler();
        $exception = new HttpException('Not Found', 404);

        $response = $handler->handle($exception);

        $this->assertEquals(404, $response->getStatusCode());
    }
}
