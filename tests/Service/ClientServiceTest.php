<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Dto\ClientDto;
use App\Entity\Client;
use App\Exception\ClientNotFoundException;
use App\Repository\ClientRepository;
use App\Service\ClientService;
use App\Service\TransactionService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class ClientServiceTest extends TestCase
{
    private ClientService $clientService;
    private $clientRepositoryMock;
    private $transactionServiceMock;

    protected function setUp(): void
    {
        $this->clientRepositoryMock = $this->createMock(ClientRepository::class);
        $this->transactionServiceMock = $this->createMock(TransactionService::class);

        $this->clientService = new ClientService(
            $this->clientRepositoryMock,
            $this->transactionServiceMock
        );
    }

    public function testCreateClient(): void
    {
        $dto = new ClientDto('John Doe', 'john@example.com', 0.0);

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Client $client) {
                return $client->getName() === 'John Doe'
                    && $client->getEmail() === 'john@example.com'
                    && $client->getBalance() === 0.0;
            }));

        $this->clientService->createClient($dto);
    }

    public function testCreateClientWithInitialBalance(): void
    {
        $dto = new ClientDto('John Doe', 'john@example.com', 100.0);

        $savedClient = null;
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Client $client) use (&$savedClient) {
                $savedClient = $client;
                $client->setId(1);
            });

        $this->transactionServiceMock
            ->expects($this->once())
            ->method('addTransaction')
            ->with($this->callback(function ($transactionDto) {
                return $transactionDto->clientId === 1
                    && $transactionDto->type === 'earning'
                    && $transactionDto->amount === 100.0
                    && $transactionDto->description === 'Initial balance';
            }));

        $this->clientService->createClient($dto);

        $this->assertNotNull($savedClient);
        $this->assertEquals(0.0, $savedClient->getBalance()); // before transaction
    }

    public function testCreateClientWithNegativeInitialBalance(): void
    {
        $dto = new ClientDto('John Doe', 'john@example.com', -50.0);

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function (Client $client) {
                $client->setId(1);
            });

        $this->transactionServiceMock
            ->expects($this->once())
            ->method('addTransaction')
            ->with($this->callback(function ($transactionDto) {
                return $transactionDto->type === 'expense'
                    && $transactionDto->amount === 50.0;
            }));

        $this->clientService->createClient($dto);
    }

    public function testGetClient(): void
    {
        $client = new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $result = $this->clientService->getClient(1);

        $this->assertInstanceOf(Client::class, $result);
        $this->assertEquals(1, $result->getId());
        $this->assertEquals('John Doe', $result->getName());
    }

    public function testGetClientThrowsExceptionWhenNotFound(): void
    {
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willThrowException(new ClientNotFoundException("Client with id 999 not found"));

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Client with id 999 not found');

        $this->clientService->getClient(999);
    }

    public function testGetAllClients(): void
    {
        $clients = [
            new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01'),
            new Client(2, 'Jane Doe', 'jane@example.com', 200.0, '2023-01-02', '2023-01-02'),
        ];

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($clients);

        $result = $this->clientService->getAllClients();

        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]->getName());
        $this->assertEquals('Jane Doe', $result[1]->getName());
    }

    public function testGetTopClientsByBalance(): void
    {
        $clients = [
            new Client(1, 'Rich Client', 'rich@example.com', 10000.0, '2023-01-01', '2023-01-01'),
            new Client(2, 'Poor Client', 'poor@example.com', 10.0, '2023-01-02', '2023-01-02'),
        ];

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('findTopByBalance')
            ->with(5)
            ->willReturn($clients);

        $result = $this->clientService->getTopClientsByBalance(5);

        $this->assertCount(2, $result);
    }

    public function testUpdateClient(): void
    {
        $client = new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Client $c) {
                return $c->getName() === 'Jane Doe'
                    && $c->getEmail() === 'jane@example.com'
                    && $c->getBalance() === 200.0;
            }));

        $dto = new ClientDto('Jane Doe', 'jane@example.com', 200.0);
        $this->clientService->updateClient(1, $dto);
    }

    public function testUpdateClientPartial(): void
    {
        $client = new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Client $c) {
                return $c->getName() === 'Jane Doe'
                    && $c->getEmail() === 'john@example.com' // unchanged
                    && $c->getBalance() === 100.0; // unchanged
            }));

        $dto = new ClientDto('Jane Doe', null, null);
        $this->clientService->updateClient(1, $dto);
    }

    public function testUpdateClientThrowsExceptionWhenNotFound(): void
    {
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willThrowException(new ClientNotFoundException("Client with id 999 not found"));

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Client with id 999 not found');

        $dto = new ClientDto('Jane Doe');
        $this->clientService->updateClient(999, $dto);
    }

    public function testDeleteClient(): void
    {
        $client = new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('delete')
            ->with(1);

        $this->clientService->deleteClient(1);
    }

    public function testDeleteClientThrowsExceptionWhenNotFound(): void
    {
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willThrowException(new ClientNotFoundException("Client with id 999 not found"));

        $this->expectException(ClientNotFoundException::class);
        $this->expectExceptionMessage('Client with id 999 not found');

        $this->clientService->deleteClient(999);
    }
}
