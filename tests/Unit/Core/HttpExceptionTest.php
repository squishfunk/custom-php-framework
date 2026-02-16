<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Exception\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $exception = new HttpException('Not found');
        
        $this->assertEquals('Not found', $exception->getMessage());
        $this->assertEquals(500, $exception->getStatusCode());
    }

    public function testConstructorWithCustomStatusCode(): void
    {
        $exception = new HttpException('Page not found', 404);
        
        $this->assertEquals('Page not found', $exception->getMessage());
        $this->assertEquals(404, $exception->getStatusCode());
    }

    public function testGetStatusCode(): void
    {
        $exception = new HttpException('Forbidden', 403);
        
        $this->assertEquals(403, $exception->getStatusCode());
    }

    public function testCommonHttpStatusCodes(): void
    {
        $testCases = [
            ['Bad Request', 400],
            ['Unauthorized', 401],
            ['Forbidden', 403],
            ['Not Found', 404],
            ['Method Not Allowed', 405],
            ['Internal Server Error', 500],
            ['Service Unavailable', 503],
        ];
        
        foreach ($testCases as [$message, $code]) {
            $exception = new HttpException($message, $code);
            $this->assertEquals($code, $exception->getStatusCode());
            $this->assertEquals($message, $exception->getMessage());
        }
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new HttpException('Error');
        
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testPreviousException(): void
    {
        $previous = new \Exception('Original error');
        $exception = new HttpException('Wrapped error', 500, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
}
