<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testConstructor(): void
    {
        $request = new Request('GET', '/test', ['id' => '1'], ['name' => 'John']);
        
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getPath());
        $this->assertEquals(['id' => '1'], $request->getQuery());
        $this->assertEquals(['name' => 'John'], $request->getPost());
    }

    public function testConstructorNormalizesMethodToUppercase(): void
    {
        $request = new Request('post', '/test');
        
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testGetMethod(): void
    {
        $request = new Request('POST', '/test');
        
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testGetPath(): void
    {
        $request = new Request('GET', '/users/123');
        
        $this->assertEquals('/users/123', $request->getPath());
    }

    public function testGetQuery(): void
    {
        $query = ['page' => '1', 'limit' => '10'];
        $request = new Request('GET', '/test', $query);
        
        $this->assertEquals($query, $request->getQuery());
    }

    public function testGetQueryReturnsEmptyArrayByDefault(): void
    {
        $request = new Request('GET', '/test');
        
        $this->assertEquals([], $request->getQuery());
    }

    public function testGetPost(): void
    {
        $post = ['email' => 'test@example.com', 'password' => 'secret'];
        $request = new Request('POST', '/test', [], $post);
        
        $this->assertEquals($post, $request->getPost());
    }

    public function testGetPostReturnsEmptyArrayByDefault(): void
    {
        $request = new Request('GET', '/test');
        
        $this->assertEquals([], $request->getPost());
    }

    public function testInputReturnsValue(): void
    {
        $request = new Request('POST', '/test', [], ['name' => 'John', 'email' => 'john@example.com']);
        
        $this->assertEquals('John', $request->input('name'));
        $this->assertEquals('john@example.com', $request->input('email'));
    }

    public function testInputReturnsNullForMissingKey(): void
    {
        $request = new Request('POST', '/test', [], ['name' => 'John']);
        
        $this->assertNull($request->input('nonexistent'));
    }

    public function testAllReturnsPostData(): void
    {
        $post = ['name' => 'John', 'email' => 'john@example.com'];
        $request = new Request('POST', '/test', [], $post);
        
        $this->assertEquals($post, $request->all());
    }

    public function testCreateFromGlobals(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/test/path?page=1';
        $_GET = ['page' => '1'];
        $_POST = ['name' => 'John'];
        
        $request = Request::createFromGlobals();
        
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/test/path', $request->getPath());
        $this->assertEquals(['page' => '1'], $request->getQuery());
        $this->assertEquals(['name' => 'John'], $request->getPost());
    }

    public function testCreateFromGlobalsWithEmptyRequestUri(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['REQUEST_URI']);
        $_GET = [];
        $_POST = [];
        
        $request = Request::createFromGlobals();
        
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/', $request->getPath());
    }
}
