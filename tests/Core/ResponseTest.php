<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $response = new Response();

        $this->assertEquals('', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testConstructorWithParameters(): void
    {
        $response = new Response('Hello World', 201, ['Content-Type' => 'text/plain']);

        $this->assertEquals('Hello World', $response->getContent());
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testSetStatusCode(): void
    {
        $response = new Response();
        $result = $response->setStatusCode(404);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertSame($response, $result);
    }

    public function testGetStatusCode(): void
    {
        $response = new Response('', 500);

        $this->assertEquals(500, $response->getStatusCode());
    }

    public function testSetContent(): void
    {
        $response = new Response();
        $result = $response->setContent('New content');

        $this->assertEquals('New content', $response->getContent());
        $this->assertSame($response, $result);
    }

    public function testGetContent(): void
    {
        $response = new Response('Test content');

        $this->assertEquals('Test content', $response->getContent());
    }

    public function testAddHeader(): void
    {
        $response = new Response();
        $result = $response->addHeader('Content-Type', 'application/json');

        $this->assertSame($response, $result);
    }

    public function testChainingMethods(): void
    {
        $response = new Response();
        $result = $response
            ->setStatusCode(201)
            ->setContent('Created')
            ->addHeader('Location', '/resource/123');

        $this->assertSame($response, $result);
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Created', $response->getContent());
    }

    public function testSend(): void
    {
        $response = new Response('Test output', 200);

        ob_start();

        $response->send();

        $output = ob_get_clean();

        $this->assertEquals('Test output', $output);
    }
}
