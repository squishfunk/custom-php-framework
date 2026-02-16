<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\CsrfToken;
use PHPUnit\Framework\TestCase;

class CsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testGenerateCreatesToken(): void
    {
        $token = CsrfToken::generate();
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testGenerateStoresTokenInSession(): void
    {
        $token = CsrfToken::generate();
        
        $this->assertEquals($token, $_SESSION['_csrf_token']);
        $this->assertArrayHasKey('_csrf_token_time', $_SESSION);
        $this->assertIsInt($_SESSION['_csrf_token_time']);
    }

    public function testGetTokenReturnsExistingToken(): void
    {
        $token1 = CsrfToken::generate();
        $token2 = CsrfToken::getToken();
        
        $this->assertEquals($token1, $token2);
    }

    public function testGetTokenGeneratesNewIfEmpty(): void
    {
        $token = CsrfToken::getToken();
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testValidateReturnsTrueForValidToken(): void
    {
        $token = CsrfToken::generate();
        
        $this->assertTrue(CsrfToken::validate($token));
    }

    public function testValidateReturnsFalseForInvalidToken(): void
    {
        CsrfToken::generate();
        
        $this->assertFalse(CsrfToken::validate('invalid-token'));
    }

    public function testValidateReturnsFalseForEmptyToken(): void
    {
        CsrfToken::generate();
        
        $this->assertFalse(CsrfToken::validate(''));
        $this->assertFalse(CsrfToken::validate(null));
    }

    public function testValidateReturnsFalseWhenNoTokenInSession(): void
    {
        $this->assertFalse(CsrfToken::validate('some-token'));
    }

    public function testClearRemovesTokenFromSession(): void
    {
        CsrfToken::generate();
        
        $this->assertArrayHasKey('_csrf_token', $_SESSION);
        
        CsrfToken::clear();
        
        $this->assertArrayNotHasKey('_csrf_token', $_SESSION);
        $this->assertArrayNotHasKey('_csrf_token_time', $_SESSION);
    }

    public function testTokenRegeneration(): void
    {
        $token1 = CsrfToken::generate();
        $token2 = CsrfToken::generate();
        
        $this->assertNotEquals($token1, $token2);
    }

    public function testGetTokenGeneratesNewAfterClear(): void
    {
        $token1 = CsrfToken::generate();
        CsrfToken::clear();
        $token2 = CsrfToken::getToken();
        
        $this->assertNotEquals($token1, $token2);
    }
}
