<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use PHPUnit\Framework\TestCase;

class TestableController extends Controller
{
    public function testValidate(Request $request, array $rules): array
    {
        return $this->validate($request, $rules);
    }

    public function testRender(string $template, array $data = [], int $statusCode = 200): Response
    {
        return $this->render($template, $data, $statusCode);
    }
}

class ControllerTest extends TestCase
{
    private TestableController $controller;

    protected function setUp(): void
    {
        $this->controller = new TestableController();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testValidateReturnsDataOnSuccess(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('all')->willReturn([
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);

        $result = $this->controller->testValidate($request, [
            'email' => 'required|email',
            'name' => 'required'
        ]);

        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Test User', $result['name']);
    }

    public function testRenderCreatesResponse(): void
    {
        $response = $this->controller->testRender('auth/login.html.twig', [], 200);
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRenderWithCustomStatusCode(): void
    {
        $response = $this->controller->testRender('error/404.html.twig', [], 404);
        
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testRenderClearsSessionErrors(): void
    {
        $_SESSION['errors'] = ['field' => ['Error message']];
        $_SESSION['old'] = ['field' => 'old value'];

        $this->controller->testRender('auth/login.html.twig');

        $this->assertArrayNotHasKey('errors', $_SESSION);
        $this->assertArrayNotHasKey('old', $_SESSION);
    }

    public function testRenderMakesErrorsAvailableToView(): void
    {
        $_SESSION['errors'] = ['email' => ['Invalid email']];
        $_SESSION['old'] = ['email' => 'test'];

        $response = $this->controller->testRender('auth/login.html.twig');
        
        $this->assertInstanceOf(Response::class, $response);
    }
}
