<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Core\Database;
use App\Dto\TransactionDto;
use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;
use App\Service\TransactionService;
use PHPUnit\Framework\TestCase;

class TransactionServiceIntegrationTest extends TestCase
{
    private TransactionService $transactionService;
    private ClientRepository $clientRepository;
    private TransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->clientRepository = new ClientRepository();
        $this->transactionRepository = new TransactionRepository();
        $this->transactionService = new TransactionService(
            $this->transactionRepository,
            $this->clientRepository
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

    public function testAddSingleEarningTransaction(): void
    {
        $client = Client::create('John Doe', 'john@example.com', 100.0);
        $this->clientRepository->save($client);

        $dto = new TransactionDto($client->getId(), 'earning', 50.0, 'Salary', '2023-01-15');
        $this->transactionService->addTransaction($dto);

        $updatedClient = $this->clientRepository->find($client->getId());
        $this->assertEquals(150.0, $updatedClient->getBalance());

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertCount(1, $transactions);
        $this->assertEquals('earning', $transactions[0]->getType());
        $this->assertEquals(50.0, $transactions[0]->getAmount());
    }

    public function testAddSingleExpenseTransaction(): void
    {
        $client = Client::create('Jane Doe', 'jane@example.com', 100.0);
        $this->clientRepository->save($client);

        $dto = new TransactionDto($client->getId(), 'expense', 30.0, 'Groceries', '2023-01-15');
        $this->transactionService->addTransaction($dto);

        $updatedClient = $this->clientRepository->find($client->getId());
        $this->assertEquals(70.0, $updatedClient->getBalance());

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertCount(1, $transactions);
        $this->assertEquals('expense', $transactions[0]->getType());
        $this->assertEquals(30.0, $transactions[0]->getAmount());
    }

    public function testAddMultipleTransactionsAndVerifyFinalBalance(): void
    {
        $client = Client::create('Test User', 'test@example.com', 1000.0);
        $this->clientRepository->save($client);

        $transactions = [
            new TransactionDto($client->getId(), 'earning', 500.0, 'Salary', '2023-01-01'),
            new TransactionDto($client->getId(), 'expense', 200.0, 'Rent', '2023-01-05'),
            new TransactionDto($client->getId(), 'expense', 150.0, 'Groceries', '2023-01-10'),
            new TransactionDto($client->getId(), 'earning', 300.0, 'Freelance', '2023-01-15'),
            new TransactionDto($client->getId(), 'expense', 50.0, 'Coffee', '2023-01-20'),
        ];

        foreach ($transactions as $dto) {
            $this->transactionService->addTransaction($dto);
        }

        $expectedBalance = 1000.0 + 500.0 - 200.0 - 150.0 + 300.0 - 50.0;

        $updatedClient = $this->clientRepository->find($client->getId());
        $this->assertEquals($expectedBalance, $updatedClient->getBalance());

        $savedTransactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertCount(5, $savedTransactions);
    }

    public function testBalanceHistoryAfterMultipleTransactions(): void
    {
        $client = Client::create('History User', 'history@example.com', 500.0);
        $this->clientRepository->save($client);

        $transactions = [
            new TransactionDto($client->getId(), 'earning', 100.0, 'First earning', '2023-01-01'),
            new TransactionDto($client->getId(), 'expense', 50.0, 'First expense', '2023-01-05'),
            new TransactionDto($client->getId(), 'earning', 200.0, 'Second earning', '2023-01-10'),
        ];

        foreach ($transactions as $dto) {
            $this->transactionService->addTransaction($dto);
        }

        $history = $this->transactionService->getBalanceHistory($client->getId());

        $this->assertCount(4, $history); // initial + 3 transactions

        $this->assertEquals(500.0, $history[0]['balance']);

        $this->assertEquals(600.0, $history[1]['balance']);

        $this->assertEquals(550.0, $history[2]['balance']);

        $this->assertEquals(750.0, $history[3]['balance']);
    }

    public function testAddTransactionWithZeroInitialBalance(): void
    {
        $client = Client::create('Zero User', 'zero@example.com', 0.0);
        $this->clientRepository->save($client);

        $dto = new TransactionDto($client->getId(), 'earning', 100.0, 'Initial deposit', '2023-01-01');
        $this->transactionService->addTransaction($dto);

        $updatedClient = $this->clientRepository->find($client->getId());
        $this->assertEquals(100.0, $updatedClient->getBalance());
    }

    public function testAddTransactionToNonExistentClient(): void
    {
        $this->expectException(\App\Exception\ClientNotFoundException::class);

        $dto = new TransactionDto(99999, 'earning', 100.0, 'Test', '2023-01-01');
        $this->transactionService->addTransaction($dto);
    }

    public function testNegativeBalanceScenario(): void
    {
        $client = Client::create('Negative User', 'negative@example.com', 50.0);
        $this->clientRepository->save($client);

        $dto = new TransactionDto($client->getId(), 'expense', 100.0, 'Big purchase', '2023-01-01');
        $this->transactionService->addTransaction($dto);

        $updatedClient = $this->clientRepository->find($client->getId());
        $this->assertEquals(-50.0, $updatedClient->getBalance());
    }
}
