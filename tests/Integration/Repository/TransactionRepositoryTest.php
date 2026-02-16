<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Core\Database;
use App\Entity\Client;
use App\Entity\Transaction;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;
use PHPUnit\Framework\TestCase;

class TransactionRepositoryTest extends TestCase
{
    private TransactionRepository $transactionRepository;
    private ClientRepository $clientRepository;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->transactionRepository = new TransactionRepository();
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

    public function testSaveTransaction(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'earning',
            50.0,
            'Test earning',
            '2023-01-15'
        );

        $this->transactionRepository->save($transaction);

        $this->assertGreaterThan(0, $transaction->getId());

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertCount(1, $transactions);
        $this->assertEquals('earning', $transactions[0]->getType());
        $this->assertEquals(50.0, $transactions[0]->getAmount());
    }

    public function testFindByClientIdReturnsTransactionsInDescendingOrder(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction1 = Transaction::create($client->getId(), 'earning', 100.0, 'First', '2023-01-01');
        $transaction2 = Transaction::create($client->getId(), 'expense', 50.0, 'Second', '2023-01-02');
        $transaction3 = Transaction::create($client->getId(), 'earning', 200.0, 'Third', '2023-01-03');

        $this->transactionRepository->save($transaction1);
        $this->transactionRepository->save($transaction2);
        $this->transactionRepository->save($transaction3);

        $transactions = $this->transactionRepository->findByClientId($client->getId());

        $this->assertCount(3, $transactions);
        $this->assertEquals('Third', $transactions[0]->getDescription());
        $this->assertEquals('Second', $transactions[1]->getDescription());
        $this->assertEquals('First', $transactions[2]->getDescription());
    }

    public function testFindByClientIdReturnsEmptyArrayForNonExistentClient(): void
    {
        $transactions = $this->transactionRepository->findByClientId(99999);

        $this->assertIsArray($transactions);
        $this->assertEmpty($transactions);
    }

    public function testFindByClientIdIsolatedBetweenClients(): void
    {
        $client1 = Client::create('Client 1', 'client1@example.com', 100.0);
        $client2 = Client::create('Client 2', 'client2@example.com', 200.0);

        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);

        $transaction1 = Transaction::create($client1->getId(), 'earning', 100.0, 'Client 1 earning', '2023-01-01');
        $this->transactionRepository->save($transaction1);

        $client1Transactions = $this->transactionRepository->findByClientId($client1->getId());
        $client2Transactions = $this->transactionRepository->findByClientId($client2->getId());

        $this->assertCount(1, $client1Transactions);
        $this->assertCount(0, $client2Transactions);
    }

    public function testSaveTransactionWithNullDescription(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'expense',
            25.0,
            null,
            '2023-01-15'
        );

        $this->transactionRepository->save($transaction);

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertNull($transactions[0]->getDescription());
    }

    public function testTransactionAmountPrecision(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 1000.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'earning',
            1234.56,
            'Precision test',
            '2023-01-15'
        );

        $this->transactionRepository->save($transaction);

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertEquals(1234.56, $transactions[0]->getAmount());
    }

    public function testTransactionWithZeroAmount(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'earning',
            0.0,
            'Zero amount',
            '2023-01-15'
        );

        $this->transactionRepository->save($transaction);

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertEquals(0.0, $transactions[0]->getAmount());
    }

    public function testFindTopClientsByVolume(): void
    {
        $client1 = Client::create('High Volume', 'high@example.com', 1000.0);
        $client2 = Client::create('Medium Volume', 'medium@example.com', 500.0);
        $client3 = Client::create('Low Volume', 'low@example.com', 100.0);

        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);

        $t1 = Transaction::create($client1->getId(), 'earning', 500.0, 'High', '2023-01-01');
        $t2 = Transaction::create($client1->getId(), 'earning', 500.0, 'High 2', '2023-01-02');
        $t3 = Transaction::create($client2->getId(), 'earning', 300.0, 'Medium', '2023-01-01');
        $t4 = Transaction::create($client3->getId(), 'earning', 100.0, 'Low', '2023-01-01');

        $this->transactionRepository->save($t1);
        $this->transactionRepository->save($t2);
        $this->transactionRepository->save($t3);
        $this->transactionRepository->save($t4);

        $topClients = $this->transactionRepository->findTopClientsByVolume(3, '2023-01-01', '2023-12-31');

        $this->assertCount(3, $topClients);
        $this->assertEquals('High Volume', $topClients[0]['name']);
        $this->assertEquals(1000.0, (float) $topClients[0]['volume']);
        $this->assertEquals('Medium Volume', $topClients[1]['name']);
        $this->assertEquals(300.0, (float) $topClients[1]['volume']);
    }

    public function testFindTopClientsByVolumeWithDefaultDates(): void
    {
        $client = Client::create('Recent Client', 'recent@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'earning',
            500.0,
            'Recent',
            date('Y-m-d')
        );
        $this->transactionRepository->save($transaction);

        $topClients = $this->transactionRepository->findTopClientsByVolume(5);

        $this->assertCount(1, $topClients);
        $this->assertEquals('Recent Client', $topClients[0]['name']);
    }

    public function testFindTopClientsByBalance(): void
    {
        $client1 = Client::create('Richest', 'rich@example.com', 10000.0);
        $client2 = Client::create('Middle', 'middle@example.com', 5000.0);
        $client3 = Client::create('Poorest', 'poor@example.com', 1000.0);

        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);

        $topClients = $this->transactionRepository->findTopClientsByBalance(3);

        $this->assertCount(3, $topClients);
        $this->assertEquals('Richest', $topClients[0]->getName());
        $this->assertEquals(10000.0, $topClients[0]->getBalance());
        $this->assertEquals('Middle', $topClients[1]->getName());
        $this->assertEquals('Poorest', $topClients[2]->getName());
    }

    public function testMultipleTransactionsForSameClient(): void
    {
        $client = Client::create('Active Client', 'active@example.com', 0.0);
        $this->clientRepository->save($client);

        $transactions = [
            Transaction::create($client->getId(), 'earning', 100.0, 'Salary', '2023-01-01'),
            Transaction::create($client->getId(), 'expense', 30.0, 'Groceries', '2023-01-02'),
            Transaction::create($client->getId(), 'expense', 20.0, 'Coffee', '2023-01-03'),
            Transaction::create($client->getId(), 'earning', 200.0, 'Freelance', '2023-01-04'),
            Transaction::create($client->getId(), 'expense', 50.0, 'Rent', '2023-01-05'),
        ];

        foreach ($transactions as $transaction) {
            $this->transactionRepository->save($transaction);
        }

        $savedTransactions = $this->transactionRepository->findByClientId($client->getId());

        $this->assertCount(5, $savedTransactions);

        $totalEarnings = 0;
        $totalExpenses = 0;

        foreach ($savedTransactions as $t) {
            if ($t->getType() === 'earning') {
                $totalEarnings += $t->getAmount();
            } else {
                $totalExpenses += $t->getAmount();
            }
        }

        $this->assertEquals(300.0, $totalEarnings);
        $this->assertEquals(100.0, $totalExpenses);
    }

    public function testTransactionTypeEarning(): void
    {
        $client = Client::create('Test', 'test@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'earning',
            100.0,
            'Test earning',
            '2023-01-15'
        );

        $this->transactionRepository->save($transaction);

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertEquals('earning', $transactions[0]->getType());
    }

    public function testTransactionTypeExpense(): void
    {
        $client = Client::create('Test', 'test@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'expense',
            50.0,
            'Test expense',
            '2023-01-15'
        );

        $this->transactionRepository->save($transaction);

        $transactions = $this->transactionRepository->findByClientId($client->getId());
        $this->assertEquals('expense', $transactions[0]->getType());
    }

    public function testTransactionDates(): void
    {
        $client = Client::create('Test', 'test@example.com', 100.0);
        $this->clientRepository->save($client);

        $transaction = Transaction::create(
            $client->getId(),
            'earning',
            100.0,
            'Test',
            '2023-12-31'
        );

        $this->transactionRepository->save($transaction);

        $transactions = $this->transactionRepository->findByClientId($client->getId());

        $this->assertStringStartsWith('2023-12-31', $transactions[0]->getDate());
        $this->assertNotNull($transactions[0]->getCreatedAt());
    }
}
