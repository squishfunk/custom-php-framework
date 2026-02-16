<?php

namespace App\Service;

use App\Core\Database;
use App\Entity\Transaction;
use App\Exception\ClientNotFoundException;
use App\Exception\InsufficientBalanceException;
use App\Repository\ClientRepositoryInterface;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepositoryInterface;
use App\Repository\TransactionRepository;
use App\Dto\TransactionDto;
use PDOException;
use Exception;

class TransactionService
{
    private TransactionRepositoryInterface $transactionRepository;
    private ClientRepositoryInterface $clientRepository;

    public function __construct(
        ?TransactionRepositoryInterface $transactionRepository = null,
        ?ClientRepositoryInterface $clientRepository = null
    ) {
        $this->transactionRepository = $transactionRepository ?? new TransactionRepository();
        $this->clientRepository = $clientRepository ?? new ClientRepository();
    }

    public function getBalanceHistory(int $clientId): array
    {
        $client = $this->clientRepository->find($clientId);
        if (!$client) {
            throw new ClientNotFoundException();
        }

        $transactions = $this->transactionRepository->findByClientId($clientId);
        $currentBalance = $client->getBalance();

        $history = [];

        $history[] = [
            'date' => date('Y-m-d H:i:s'),
            'balance' => $currentBalance
        ];

        $balance = $currentBalance;

        foreach ($transactions as $transaction) {
            $amount = $transaction->getAmount();
            $type = $transaction->getType();

            if ($type === 'earning') {
                $balance -= $amount;
            } else {
                $balance += $amount;
            }

            $history[] = [
                'date' => $transaction->getDate(),
                'balance' => $balance
            ];
        }

        // Return chronologically (oldest to newest)
        return array_reverse($history);
    }

    public function addTransaction(TransactionDto $dto): void
    {
        $client = $this->clientRepository->find($dto->clientId);

        if (!$client) {
            throw new ClientNotFoundException();
        }

        $currentBalance = $client->getBalance();
        $newBalance = $this->calculateNewBalance($currentBalance, $dto->type, $dto->amount);

        if ($newBalance < 0) {
            throw new InsufficientBalanceException();
        }

        Database::beginTransaction();

        try {
            $transaction = Transaction::create(
                $dto->clientId,
                $dto->type,
                $dto->amount,
                $dto->description,
                $dto->date
            );
            $this->transactionRepository->save($transaction);

            $client->setBalance($newBalance);
            $this->clientRepository->update($client);

            Database::commit();
        } catch (PDOException $e) {
            Database::rollBack();
            throw new Exception('Failed to process transaction: ' . $e->getMessage());
        }
    }

    private function calculateNewBalance(float $currentBalance, string $type, float $amount): float
    {
        return match ($type) {
            'expense' => $currentBalance - $amount,
            'earning' => $currentBalance + $amount,
            default => throw new Exception('Invalid transaction type: ' . $type),
        };
    }

    public function getClientTransactions(int $clientId): array
    {
        return $this->transactionRepository->findByClientId($clientId);
    }
}
