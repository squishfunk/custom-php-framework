<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Repository\ClientRepository;
use App\Repository\TransactionRepository;
use App\Dto\TransactionDto;

class TransactionService
{
    private TransactionRepository $transactionRepository;
    private ClientRepository $clientRepository;

    public function __construct()
    {
        $this->transactionRepository = new TransactionRepository();
        $this->clientRepository = new ClientRepository();
    }

    public function addTransaction(TransactionDto $dto): void
    {
        $client = $this->clientRepository->find($dto->clientId);

        if (!$client) {
            throw new \Exception("Client not found"); // TODO Should use custom exception
        }

        $transaction = Transaction::create(
            $dto->clientId,
            $dto->type,
            $dto->amount,
            $dto->description,
            $dto->date
        );

        $this->transactionRepository->save($transaction);

        $currentBalance = $client->getBalance();
        if ($dto->type === 'expense') {
            $client->setBalance($currentBalance - $dto->amount);
        } else {
            $client->setBalance($currentBalance + $dto->amount);
        }
        $this->clientRepository->update($client);
    }

    public function getClientTransactions(int $clientId): array
    {
        return $this->transactionRepository->findByClientId($clientId);
    }
}
