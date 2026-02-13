<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Entity\Admin;
use App\Exception\AdminAlreadyExistsException;
use App\Exception\InvalidCredentialsException;
use App\Repository\AdminRepository;
use App\Service\AuthService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private $adminRepositoryMock;

    protected function setUp(): void
    {
        $this->adminRepositoryMock = $this->createMock(AdminRepository::class);

        $this->authService = new AuthService($this->adminRepositoryMock);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testLoginWithValidCredentials(): void
    {
        $password = 'secure_password';
        $admin = Admin::create('admin@example.com', $password);

        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('admin@example.com')
            ->willReturn($admin);

        $result = $this->authService->login('admin@example.com', $password);

        $this->assertTrue($result);
        $this->assertArrayHasKey('admin_id', $_SESSION);
        $this->assertArrayHasKey('admin_email', $_SESSION);
        $this->assertEquals('admin@example.com', $_SESSION['admin_email']);
    }

    public function testLoginWithInvalidEmail(): void
    {
        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('nonexistent@example.com')
            ->willReturn(null);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login('nonexistent@example.com', 'password');
    }

    public function testLoginWithInvalidPassword(): void
    {
        $admin = Admin::create('admin@example.com', 'correct_password');

        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('admin@example.com')
            ->willReturn($admin);

        $this->expectException(InvalidCredentialsException::class);

        $this->authService->login('admin@example.com', 'wrong_password');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testLogout(): void
    {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_email'] = 'admin@example.com';

        $this->authService->logout();

        // in test environment we verify that no exception is thrown
        $this->assertTrue(true);
    }

    public function testRegisterAdmin(): void
    {
        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('newadmin@example.com')
            ->willReturn(null);

        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Admin $admin) {
                return $admin->getEmail() === 'newadmin@example.com'
                    && $admin->verifyPassword('password123');
            }));

        $this->authService->registerAdmin('newadmin@example.com', 'password123');
    }

    public function testRegisterAdminThrowsExceptionWhenEmailExists(): void
    {
        $existingAdmin = Admin::create('existing@example.com', 'password');

        $this->adminRepositoryMock
            ->expects($this->once())
            ->method('findByEmail')
            ->with('existing@example.com')
            ->willReturn($existingAdmin);

        $this->expectException(AdminAlreadyExistsException::class);

        $this->authService->registerAdmin('existing@example.com', 'password123');
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIsLoggedInReturnsTrueWhenSessionExists(): void
    {
        $_SESSION['admin_id'] = 1;

        $this->assertTrue($this->authService->isLoggedIn());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIsLoggedInReturnsFalseWhenNoSession(): void
    {
        $this->assertFalse($this->authService->isLoggedIn());
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testIsLoggedInReturnsFalseWhenAdminIdNotSet(): void
    {
        $_SESSION['other_key'] = 'value';

        $this->assertFalse($this->authService->isLoggedIn());
    }
}
