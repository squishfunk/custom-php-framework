<?php

namespace App\Service;

use App\Dto\ClientDto;
use App\Dto\TransactionDto;
use App\Entity\Client;
use App\Exception\ClientAlreadyExistsException;
use App\Exception\ClientNotFoundException;
use App\Repository\ClientRepository;

class ClientService
{
    private ClientRepository $clientRepository;
    private TransactionService $transactionService;

    public function __construct(
        ?ClientRepository $clientRepository = null,
        ?TransactionService $transactionService = null
    ) {
        $this->clientRepository = $clientRepository ?? new ClientRepository();
        $this->transactionService = $transactionService ?? new TransactionService();
    }

    public function createClient(ClientDto $dto): void
    {
        if ($this->clientRepository->findByEmail($dto->email)) {
            throw new ClientAlreadyExistsException();
        }

        $initialBalance = $dto->balance;
        $client = Client::create($dto->name, $dto->email, 0.0);
        $this->clientRepository->save($client);

        if ($initialBalance != 0) {
            $transactionDto = new TransactionDto(
                $client->getId(),
                $initialBalance > 0 ? 'earning' : 'expense', // ability to add debt to client
                abs($initialBalance),
                'Initial balance',
                date('Y-m-d H:i:s')
            );


            $this->transactionService->addTransaction($transactionDto);
        }
    }

    public function getClient(int $id): ?Client
    {
        return $this->clientRepository->find($id);
    }

    /**
     * @return Client[]
     */
    public function getAllClients(): array
    {
        return $this->clientRepository->findAll();
    }

    public function updateClient(int $id, ClientDto $dto): void
    {
        $client = $this->clientRepository->find($id);

        if (!$client) {
            throw new ClientNotFoundException("Client with id $id not found");
        }

        if ($dto->name !== null) {
            $client->setName($dto->name);
        }

        if ($dto->email !== null) {
            $client->setEmail($dto->email);
        }

        $this->clientRepository->update($client);
    }

    public function deleteClient(int $id): void
    {
        $client = $this->clientRepository->find($id);
        if (!$client) {
            throw new ClientNotFoundException("Client with id $id not found");
        }

        $this->clientRepository->delete($id);
    }
}
