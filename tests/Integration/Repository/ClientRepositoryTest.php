<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Core\Database;
use App\Entity\Client;
use App\Exception\ClientNotFoundException;
use App\Repository\ClientRepository;
use PHPUnit\Framework\TestCase;

class ClientRepositoryTest extends TestCase
{
    private ClientRepository $clientRepository;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->clientRepository = new ClientRepository();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $db = Database::getConnection(true);
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $db->exec('DELETE FROM transactions');
        $db->exec('DELETE FROM clients');
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testSaveClient(): void
    {
        $client = Client::create('John Doe', 'john@example.com', 100.0);
        
        $this->clientRepository->save($client);
        
        $this->assertGreaterThan(0, $client->getId());
        
        $savedClient = $this->clientRepository->find($client->getId());
        $this->assertEquals('John Doe', $savedClient->getName());
        $this->assertEquals('john@example.com', $savedClient->getEmail());
        $this->assertEquals(100.0, $savedClient->getBalance());
    }

    public function testFindClientById(): void
    {
        $client = Client::create('Jane Doe', 'jane@example.com', 200.0);
        $this->clientRepository->save($client);
        
        $foundClient = $this->clientRepository->find($client->getId());
        
        $this->assertInstanceOf(Client::class, $foundClient);
        $this->assertEquals('Jane Doe', $foundClient->getName());
        $this->assertEquals('jane@example.com', $foundClient->getEmail());
    }

    public function testFindThrowsExceptionWhenClientNotFound(): void
    {
        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Client with id 99999 not found');
        
        $this->clientRepository->find(99999);
    }

    public function testFindAllClients(): void
    {
        $client1 = Client::create('Client One', 'client1@example.com', 100.0);
        $client2 = Client::create('Client Two', 'client2@example.com', 200.0);
        $client3 = Client::create('Client Three', 'client3@example.com', 300.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);
        
        $clients = $this->clientRepository->findAll();
        
        $this->assertCount(3, $clients);
        $this->assertContainsOnlyInstancesOf(Client::class, $clients);
    }

    public function testFindAllReturnsEmptyArrayWhenNoClients(): void
    {
        $clients = $this->clientRepository->findAll();
        
        $this->assertIsArray($clients);
        $this->assertEmpty($clients);
    }

    public function testFindByEmail(): void
    {
        $client = Client::create('Email Test', 'email@example.com', 150.0);
        $this->clientRepository->save($client);
        
        $foundClient = $this->clientRepository->findByEmail('email@example.com');
        
        $this->assertInstanceOf(Client::class, $foundClient);
        $this->assertEquals('Email Test', $foundClient->getName());
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $result = $this->clientRepository->findByEmail('nonexistent@example.com');
        
        $this->assertNull($result);
    }

    public function testFindByEmailExceptIdReturnsNullWhenSameId(): void
    {
        $client = Client::create('Single', 'single@example.com', 100.0);
        $this->clientRepository->save($client);
        
        $result = $this->clientRepository->findByEmailExceptId('single@example.com', $client->getId());
        
        $this->assertNull($result);
    }

    public function testFindByEmailExceptIdReturnsNullWhenEmailNotFound(): void
    {
        $client = Client::create('Test', 'test@example.com', 100.0);
        $this->clientRepository->save($client);
        
        $result = $this->clientRepository->findByEmailExceptId('nonexistent@example.com', $client->getId());
        
        $this->assertNull($result);
    }

    public function testUpdateClient(): void
    {
        $client = Client::create('Original Name', 'original@example.com', 100.0);
        $this->clientRepository->save($client);
        
        $client->setName('Updated Name');
        $client->setEmail('updated@example.com');
        $client->setBalance(500.0);
        
        $this->clientRepository->update($client);
        
        $updatedClient = $this->clientRepository->find($client->getId());
        $this->assertEquals('Updated Name', $updatedClient->getName());
        $this->assertEquals('updated@example.com', $updatedClient->getEmail());
        $this->assertEquals(500.0, $updatedClient->getBalance());
    }

    public function testDeleteClient(): void
    {
        $client = Client::create('To Delete', 'delete@example.com', 100.0);
        $this->clientRepository->save($client);
        $clientId = $client->getId();
        
        $this->clientRepository->delete($clientId);
        
        $this->expectException(ClientNotFoundException::class);
        $this->clientRepository->find($clientId);
    }

    public function testFindPaginatedFirstPage(): void
    {
        // Create 15 clients
        for ($i = 1; $i <= 15; $i++) {
            $client = Client::create("Client $i", "client$i@example.com", (float) ($i * 10));
            $this->clientRepository->save($client);
        }
        
        $result = $this->clientRepository->findPaginated(1, 10);
        
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('pages', $result);
        $this->assertCount(10, $result['items']);
        $this->assertEquals(15, $result['total']);
        $this->assertEquals(2, $result['pages']);
    }

    public function testFindPaginatedSecondPage(): void
    {
        // Create 15 clients
        for ($i = 1; $i <= 15; $i++) {
            $client = Client::create("Client $i", "client$i@example.com", (float) ($i * 10));
            $this->clientRepository->save($client);
        }
        
        $result = $this->clientRepository->findPaginated(2, 10);
        
        $this->assertCount(5, $result['items']);
        $this->assertEquals(15, $result['total']);
    }

    public function testFindPaginatedEmptyResult(): void
    {
        $result = $this->clientRepository->findPaginated(1, 10);
        
        $this->assertEmpty($result['items']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['pages']);
    }

    public function testClientBalancePrecision(): void
    {
        $client = Client::create('Precision Test', 'precision@example.com', 1234.56);
        $this->clientRepository->save($client);
        
        $foundClient = $this->clientRepository->find($client->getId());
        
        $this->assertEquals(1234.56, $foundClient->getBalance());
    }

    public function testClientWithZeroBalance(): void
    {
        $client = Client::create('Zero Balance', 'zero@example.com', 0.0);
        $this->clientRepository->save($client);
        
        $foundClient = $this->clientRepository->find($client->getId());
        
        $this->assertEquals(0.0, $foundClient->getBalance());
    }

    public function testClientWithNegativeBalance(): void
    {
        $client = Client::create('Negative Balance', 'negative@example.com', -500.0);
        $this->clientRepository->save($client);
        
        $foundClient = $this->clientRepository->find($client->getId());
        
        $this->assertEquals(-500.0, $foundClient->getBalance());
    }

    public function testMultipleClientsWithSameBalance(): void
    {
        $client1 = Client::create('Client A', 'a@example.com', 100.0);
        $client2 = Client::create('Client B', 'b@example.com', 100.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        
        $clients = $this->clientRepository->findAll();
        
        $this->assertCount(2, $clients);
        $this->assertEquals(100.0, $clients[0]->getBalance());
        $this->assertEquals(100.0, $clients[1]->getBalance());
    }
}
