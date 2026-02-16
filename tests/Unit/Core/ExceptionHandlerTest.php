<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Config;
use App\Core\ExceptionHandler;
use App\Core\Exception\HttpException;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
    private ExceptionHandler $handler;
    private string $tempConfigFile;

    protected function setUp(): void
    {
        $this->handler = new ExceptionHandler();
        
        // Set up test config
        $tempFile = sys_get_temp_dir() . '/config_test_' . uniqid() . '.php';
        file_put_contents($tempFile, '<?php return ["app" => ["env" => "prod"]];');
        Config::load($tempFile);
        $this->tempConfigFile = $tempFile;
    }

    protected function tearDown(): void
    {
        if (isset($this->tempConfigFile) && file_exists($this->tempConfigFile)) {
            unlink($this->tempConfigFile);
        }
    }

    public function testHandleHttpException404(): void
    {
        $exception = new HttpException('Page not found', 404);
        
        $response = $this->handler->handle($exception);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testHandleHttpException500(): void
    {
        $exception = new HttpException('Server error', 500);
        
        $response = $this->handler->handle($exception);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testHandleGenericException(): void
    {
        $exception = new \Exception('Something went wrong');
        
        $response = $this->handler->handle($exception);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testHandleVariousStatusCodes(): void
    {
        $testCodes = [400, 401, 403, 404, 405, 500, 502, 503];
        
        foreach ($testCodes as $code) {
            $exception = new HttpException('Error', $code);
            $response = $this->handler->handle($exception);
            
            $this->assertEquals($code, $response->getStatusCode());
        }
    }
}
