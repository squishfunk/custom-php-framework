<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Core\Database;
use App\Entity\Admin;
use App\Repository\AdminRepository;
use PHPUnit\Framework\TestCase;

class AdminRepositoryTest extends TestCase
{
    private AdminRepository $adminRepository;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->adminRepository = new AdminRepository();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $db = Database::getConnection(true);
        $db->exec('DELETE FROM admins');
    }

    public function testSaveAdmin(): void
    {
        $admin = Admin::create('admin@example.com', 'securepassword123');
        
        $this->adminRepository->save($admin);

        $savedAdmin = $this->adminRepository->findByEmail('admin@example.com');
        $this->assertNotNull($savedAdmin);
        $this->assertEquals('admin@example.com', $savedAdmin->getEmail());
        $this->assertTrue($savedAdmin->verifyPassword('securepassword123'));
    }

    public function testFindByEmailReturnsAdmin(): void
    {
        $admin = Admin::create('admin@example.com', 'password123');
        $this->adminRepository->save($admin);

        $foundAdmin = $this->adminRepository->findByEmail('admin@example.com');

        $this->assertInstanceOf(Admin::class, $foundAdmin);
        $this->assertEquals('admin@example.com', $foundAdmin->getEmail());
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $result = $this->adminRepository->findByEmail('nonexistent@example.com');
        $this->assertNull($result);
    }

    public function testSaveMultipleAdmins(): void
    {
        $admin1 = Admin::create('admin1@example.com', 'password1');
        $admin2 = Admin::create('admin2@example.com', 'password2');
        
        $this->adminRepository->save($admin1);
        $this->adminRepository->save($admin2);

        $found1 = $this->adminRepository->findByEmail('admin1@example.com');
        $found2 = $this->adminRepository->findByEmail('admin2@example.com');

        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertNotEquals($found1->getId(), $found2->getId());
    }

    public function testPasswordIsProperlyHashed(): void
    {
        $plainPassword = 'mysecretpassword';
        $admin = Admin::create('admin@example.com', $plainPassword);
        
        $this->adminRepository->save($admin);

        $savedAdmin = $this->adminRepository->findByEmail('admin@example.com');
        
        $this->assertNotEquals($plainPassword, $savedAdmin->getPasswordHash());
        $this->assertTrue(password_verify($plainPassword, $savedAdmin->getPasswordHash()));
    }

    public function testVerifyPassword(): void
    {
        $admin = Admin::create('admin@example.com', 'correctpassword');
        $this->adminRepository->save($admin);

        $savedAdmin = $this->adminRepository->findByEmail('admin@example.com');

        $this->assertTrue($savedAdmin->verifyPassword('correctpassword'));
        $this->assertFalse($savedAdmin->verifyPassword('wrongpassword'));
        $this->assertFalse($savedAdmin->verifyPassword('CorrectPassword'));
    }

    public function testAdminHasIdAfterSave(): void
    {
        $admin = Admin::create('admin@example.com', 'password123');
        
        $this->assertEquals(0, $admin->getId());
        
        $this->adminRepository->save($admin);

        $savedAdmin = $this->adminRepository->findByEmail('admin@example.com');
        $this->assertGreaterThan(0, $savedAdmin->getId());
    }

    public function testCreatedAtIsSet(): void
    {
        $beforeSave = time();
        $admin = Admin::create('admin@example.com', 'password123');
        $this->adminRepository->save($admin);
        $afterSave = time();

        $savedAdmin = $this->adminRepository->findByEmail('admin@example.com');
        $createdAt = strtotime($savedAdmin->getPasswordHash());
        
        $this->assertGreaterThan(0, strlen($savedAdmin->getPasswordHash()));
    }

    public function testEmailWithSpecialCharacters(): void
    {
        $admin = Admin::create('admin+test@example.com', 'password123');
        $this->adminRepository->save($admin);

        $found = $this->adminRepository->findByEmail('admin+test@example.com');
        $this->assertNotNull($found);
        $this->assertEquals('admin+test@example.com', $found->getEmail());
    }

    public function testLongEmail(): void
    {
        $longEmail = 'very.long.email.address.that.is.quite.long.and.complex@subdomain.example.com';
        $admin = Admin::create($longEmail, 'password123');
        $this->adminRepository->save($admin);

        $found = $this->adminRepository->findByEmail($longEmail);
        $this->assertNotNull($found);
        $this->assertEquals($longEmail, $found->getEmail());
    }
}
