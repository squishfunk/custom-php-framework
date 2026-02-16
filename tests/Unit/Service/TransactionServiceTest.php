<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Dto\TransactionDto;
use App\Entity\Client;
use App\Entity\Transaction;
use App\Exception\ClientNotFoundException;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;
use App\Service\TransactionService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class TransactionServiceTest extends TestCase
{
    private TransactionService $transactionService;
    private $transactionRepositoryMock;
    private $clientRepositoryMock;

    protected function setUp(): void
    {
        $this->transactionRepositoryMock = $this->createMock(TransactionRepository::class);
        $this->clientRepositoryMock = $this->createMock(ClientRepository::class);

        $this->transactionService = new TransactionService(
            $this->transactionRepositoryMock,
            $this->clientRepositoryMock
        );
    }

    public function testGetBalanceHistory(): void
    {
        // init balance of client = 80
        $client = new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01');

        $transactions = [
            new Transaction(1, 1, 'earning', 50.0, 'Payment', '2023-01-15', '2023-01-15'),
            new Transaction(2, 1, 'expense', 30.0, 'Purchase', '2023-01-10', '2023-01-10'),
        ];



        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $this->transactionRepositoryMock
            ->expects($this->once())
            ->method('findByClientId')
            ->with(1)
            ->willReturn($transactions);

        $history = $this->transactionService->getBalanceHistory(1);

        $this->assertCount(3, $history);

        // init balance 
        $this->assertEquals(80.0, $history[0]['balance']);

        // balance before second transaction
        $this->assertEquals(50.0, $history[1]['balance']);

        // current balance
        $this->assertEquals(100.0, $history[2]['balance']);
    }

    public function testGetBalanceHistoryThrowsExceptionWhenClientNotFound(): void
    {
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willThrowException(new ClientNotFoundException());

        $this->expectException(ClientNotFoundException::class);

        $this->transactionService->getBalanceHistory(999);
    }

    public function testAddTransactionEarning(): void
    {
        $client = new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $this->transactionRepositoryMock
            ->expects($this->once())
            ->method('save');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Client $c) {
                return $c->getBalance() === 150.0; // 100 + 50
            }));

        $dto = new TransactionDto(1, 'earning', 50.0, 'Salary', '2023-01-15');
        $this->transactionService->addTransaction($dto);
    }

    public function testAddTransactionExpense(): void
    {
        $client = new Client(1, 'John Doe', 'john@example.com', 100.0, '2023-01-01', '2023-01-01');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($client);

        $this->transactionRepositoryMock
            ->expects($this->once())
            ->method('save');

        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(function (Client $c) {
                return $c->getBalance() === 70.0; // 100 - 30
            }));

        $dto = new TransactionDto(1, 'expense', 30.0, 'Groceries', '2023-01-15');
        $this->transactionService->addTransaction($dto);
    }

    public function testAddTransactionThrowsExceptionWhenClientNotFound(): void
    {
        $this->clientRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willThrowException(new ClientNotFoundException());

        $this->expectException(ClientNotFoundException::class);

        $dto = new TransactionDto(999, 'earning', 50.0, 'Test', '2023-01-15');
        $this->transactionService->addTransaction($dto);
    }

    public function testGetClientTransactions(): void
    {
        $transactions = [
            new Transaction(1, 1, 'earning', 50.0, 'Payment', '2023-01-15', '2023-01-15'),
            new Transaction(2, 1, 'expense', 30.0, 'Purchase', '2023-01-10', '2023-01-10'),
        ];

        $this->transactionRepositoryMock
            ->expects($this->once())
            ->method('findByClientId')
            ->with(1)
            ->willReturn($transactions);

        $result = $this->transactionService->getClientTransactions(1);

        $this->assertCount(2, $result);
    }
}
