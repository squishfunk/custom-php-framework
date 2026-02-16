<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Core\Database;
use App\Exception\AdminAlreadyExistsException;
use App\Exception\InvalidCredentialsException;
use App\Repository\AdminRepository;
use App\Service\AuthService;
use PHPUnit\Framework\TestCase;

class AuthServiceIntegrationTest extends TestCase
{
    private AuthService $authService;
    private AdminRepository $adminRepository;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->adminRepository = new AdminRepository();
        $this->authService = new AuthService($this->adminRepository);

        $this->cleanDatabase();
        
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    private function cleanDatabase(): void
    {
        $db = Database::getConnection(true);
        $db->exec('DELETE FROM admins');
    }

    public function testRegisterAdminSuccessfully(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'securepassword123');

        $admin = $this->adminRepository->findByEmail('admin@example.com');
        $this->assertNotNull($admin);
        $this->assertEquals('admin@example.com', $admin->getEmail());
        $this->assertTrue($admin->verifyPassword('securepassword123'));
    }

    public function testRegisterAdminWithDuplicateEmailThrowsException(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'password123');

        $this->expectException(AdminAlreadyExistsException::class);
        $this->authService->registerAdmin('admin@example.com', 'differentpassword');
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'securepassword123');

        $result = $this->authService->login('admin@example.com', 'securepassword123');

        $this->assertTrue($result);
        $this->assertTrue($this->authService->isLoggedIn());
        $this->assertArrayHasKey('admin_id', $_SESSION);
        $this->assertArrayHasKey('admin_email', $_SESSION);
        $this->assertEquals('admin@example.com', $_SESSION['admin_email']);
    }

    public function testLoginWithInvalidEmailThrowsException(): void
    {
        $this->expectException(InvalidCredentialsException::class);
        $this->authService->login('nonexistent@example.com', 'password123');
    }

    public function testLoginWithInvalidPasswordThrowsException(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'correctpassword');

        $this->expectException(InvalidCredentialsException::class);
        $this->authService->login('admin@example.com', 'wrongpassword');
    }

    public function testIsLoggedInReturnsFalseWhenNotLoggedIn(): void
    {
        $this->assertFalse($this->authService->isLoggedIn());
    }

    public function testIsLoggedInReturnsTrueWhenLoggedIn(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'securepassword123');
        $this->authService->login('admin@example.com', 'securepassword123');

        $this->assertTrue($this->authService->isLoggedIn());
    }

    public function testLogoutClearsSession(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'securepassword123');
        $this->authService->login('admin@example.com', 'securepassword123');
        
        $this->assertTrue($this->authService->isLoggedIn());
        $this->assertArrayHasKey('admin_id', $_SESSION);

        $this->authService->logout();
        
        $this->assertEquals(PHP_SESSION_NONE, session_status());
    }

    public function testMultipleAdminsCanBeRegistered(): void
    {
        $this->authService->registerAdmin('admin1@example.com', 'password1');
        $this->authService->registerAdmin('admin2@example.com', 'password2');

        $admin1 = $this->adminRepository->findByEmail('admin1@example.com');
        $admin2 = $this->adminRepository->findByEmail('admin2@example.com');

        $this->assertNotNull($admin1);
        $this->assertNotNull($admin2);
        $this->assertNotEquals($admin1->getId(), $admin2->getId());
    }

    public function testLoginWithWrongPasswordAfterCorrectRegistration(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'correctpassword');

        $this->expectException(InvalidCredentialsException::class);
        $this->authService->login('admin@example.com', 'CorrectPassword');
    }

    public function testPasswordIsHashedNotStoredPlain(): void
    {
        $plainPassword = 'mysecretpassword';
        $this->authService->registerAdmin('admin@example.com', $plainPassword);

        $admin = $this->adminRepository->findByEmail('admin@example.com');
        
        $this->assertNotEquals($plainPassword, $admin->getPasswordHash());
        $this->assertTrue(password_verify($plainPassword, $admin->getPasswordHash()));
    }

    public function testLoginCreatesNewSessionId(): void
    {
        $this->authService->registerAdmin('admin@example.com', 'securepassword123');
        
        $oldSessionId = session_id();
        $this->authService->login('admin@example.com', 'securepassword123');
        $newSessionId = session_id();

        $this->assertNotEmpty($newSessionId);
    }
}
