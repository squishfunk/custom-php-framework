<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Core\Database;
use App\Dto\ClientDto;
use App\Exception\ClientAlreadyExistsException;
use App\Exception\ClientNotFoundException;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;
use App\Service\ClientService;
use App\Service\TransactionService;
use PHPUnit\Framework\TestCase;

class ClientServiceIntegrationTest extends TestCase
{
    private ClientService $clientService;
    private ClientRepository $clientRepository;
    private TransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->clientRepository = new ClientRepository();
        $this->transactionRepository = new TransactionRepository();
        $transactionService = new TransactionService(
            $this->transactionRepository,
            $this->clientRepository
        );
        $this->clientService = new ClientService(
            $this->clientRepository,
            $transactionService
        );

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

    public function testCreateClientWithZeroBalance(): void
    {
        $dto = new ClientDto('John Doe', 'john@example.com', 0.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $this->assertCount(1, $clients);
        $this->assertEquals('John Doe', $clients[0]->getName());
        $this->assertEquals('john@example.com', $clients[0]->getEmail());
        $this->assertEquals(0.0, $clients[0]->getBalance());
    }

    public function testCreateClientWithPositiveBalance(): void
    {
        $dto = new ClientDto('Jane Doe', 'jane@example.com', 500.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $this->assertCount(1, $clients);
        $this->assertEquals(500.0, $clients[0]->getBalance());

        $transactions = $this->transactionRepository->findByClientId($clients[0]->getId());
        $this->assertCount(1, $transactions);
        $this->assertEquals('earning', $transactions[0]->getType());
        $this->assertEquals(500.0, $transactions[0]->getAmount());
    }

    public function testCreateClientWithNegativeBalance(): void
    {
        $dto = new ClientDto('Debtor', 'debtor@example.com', -200.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $this->assertCount(1, $clients);
        $this->assertEquals(-200.0, $clients[0]->getBalance());

        $transactions = $this->transactionRepository->findByClientId($clients[0]->getId());
        $this->assertCount(1, $transactions);
        $this->assertEquals('expense', $transactions[0]->getType());
        $this->assertEquals(200.0, $transactions[0]->getAmount());
    }

    public function testCreateClientWithDuplicateEmailThrowsException(): void
    {
        $dto1 = new ClientDto('First User', 'duplicate@example.com', 100.0);
        $this->clientService->createClient($dto1);

        $dto2 = new ClientDto('Second User', 'duplicate@example.com', 200.0);
        
        $this->expectException(ClientAlreadyExistsException::class);
        $this->clientService->createClient($dto2);
    }

    public function testGetClientById(): void
    {
        $dto = new ClientDto('Test User', 'test@example.com', 0.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $clientId = $clients[0]->getId();

        $foundClient = $this->clientService->getClient($clientId);
        
        $this->assertNotNull($foundClient);
        $this->assertEquals('Test User', $foundClient->getName());
        $this->assertEquals('test@example.com', $foundClient->getEmail());
    }

    public function testGetNonExistentClientThrowsException(): void
    {
        $this->expectException(\App\Exception\ClientNotFoundException::class);
        $this->clientService->getClient(99999);
    }

    public function testGetAllClients(): void
    {
        $this->clientService->createClient(new ClientDto('User 1', 'user1@example.com', 100.0));
        $this->clientService->createClient(new ClientDto('User 2', 'user2@example.com', 200.0));
        $this->clientService->createClient(new ClientDto('User 3', 'user3@example.com', 300.0));

        $clients = $this->clientService->getAllClients();
        
        $this->assertCount(3, $clients);
    }

    public function testGetClientsPaginated(): void
    {
        for ($i = 1; $i <= 15; $i++) {
            $this->clientService->createClient(
                new ClientDto("User $i", "user$i@example.com", (float) ($i * 10))
            );
        }

        $result = $this->clientService->getClientsPaginated(1, 10);
        
        $this->assertCount(10, $result['items']);
        $this->assertEquals(15, $result['total']);
        $this->assertEquals(2, $result['pages']);
        $this->assertEquals(1, $result['page']);
    }

    public function testUpdateClientName(): void
    {
        $dto = new ClientDto('Original Name', 'update@example.com', 0.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $clientId = $clients[0]->getId();

        $updateDto = new ClientDto('Updated Name', null, null);
        $this->clientService->updateClient($clientId, $updateDto);

        $updatedClient = $this->clientService->getClient($clientId);
        $this->assertEquals('Updated Name', $updatedClient->getName());
        $this->assertEquals('update@example.com', $updatedClient->getEmail());
    }

    public function testUpdateClientEmail(): void
    {
        $dto = new ClientDto('Test User', 'old@example.com', 0.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $clientId = $clients[0]->getId();

        $updateDto = new ClientDto(null, 'new@example.com', null);
        $this->clientService->updateClient($clientId, $updateDto);

        $updatedClient = $this->clientService->getClient($clientId);
        $this->assertEquals('new@example.com', $updatedClient->getEmail());
    }

    public function testUpdateClientWithDuplicateEmailThrowsException(): void
    {
        $this->clientService->createClient(new ClientDto('User 1', 'user1@example.com', 0.0));
        $this->clientService->createClient(new ClientDto('User 2', 'user2@example.com', 0.0));

        $clients = $this->clientService->getAllClients();
        $client2Id = $clients[1]->getId();

        $updateDto = new ClientDto(null, 'user1@example.com', null);
        
        $this->expectException(ClientAlreadyExistsException::class);
        $this->clientService->updateClient($client2Id, $updateDto);
    }

    public function testUpdateNonExistentClientThrowsException(): void
    {
        $dto = new ClientDto('Updated Name', null, null);
        
        $this->expectException(ClientNotFoundException::class);
        $this->clientService->updateClient(99999, $dto);
    }

    public function testDeleteClient(): void
    {
        $dto = new ClientDto('To Delete', 'delete@example.com', 0.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $clientId = $clients[0]->getId();

        $this->clientService->deleteClient($clientId);

        $remainingClients = $this->clientService->getAllClients();
        $this->assertCount(0, $remainingClients);
    }

    public function testDeleteNonExistentClientThrowsException(): void
    {
        $this->expectException(ClientNotFoundException::class);
        $this->clientService->deleteClient(99999);
    }

    public function testDeleteClientWithTransactions(): void
    {
        $dto = new ClientDto('With Transactions', 'transactions@example.com', 100.0);
        $this->clientService->createClient($dto);

        $clients = $this->clientService->getAllClients();
        $clientId = $clients[0]->getId();

        $this->assertCount(1, $this->transactionRepository->findByClientId($clientId));

        $this->clientService->deleteClient($clientId);

        $transactions = $this->transactionRepository->findByClientId($clientId);
        $this->assertCount(0, $transactions);
    }
}
