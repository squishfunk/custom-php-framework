<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Dto\ClientDto;
use App\Service\ClientService;
use App\Service\TransactionService;

class ClientController extends Controller
{
    private ClientService $clientService;
    private TransactionService $transactionService;

    public function __construct()
    {
        $this->clientService = new ClientService();
        $this->transactionService = new TransactionService();
    }

    public function store(Request $request)
    {
        $dto = new ClientDto(
            $request->input("name"),
            $request->input("email"),
            (float) $request->input("balance")
        );

        $this->clientService->createClient($dto);
        $this->redirect('/');
    }

    public function create()
    {
        return $this->render('client/create.html.twig');
    }

    public function update(Request $request, string $id)
    {
        $dto = new ClientDto(
            $request->input('name'),
            $request->input('email'),
            $request->input('balance') !== null ? (float) $request->input('balance') : null
        );

        $this->clientService->updateClient((int) $id, $dto);

        return new Response('Client updated');
    }

    public function show(Request $request, string $id)
    {
        $client = $this->clientService->getClient((int) $id);

        if (!$client) {
            return new Response('Client not found', 404);
        }

        $transactions = $this->transactionService->getClientTransactions((int) $id);

        return $this->render('client/show.html.twig', [
            'client' => $client,
            'transactions' => $transactions
        ]);
    }

    public function delete(Request $request, string $id)
    {
        $this->clientService->deleteClient((int) $id);
        $this->redirect('/');
    }

    public function index()
    {
        $clients = $this->clientService->getAllClients();

        return $this->render('client/index.html.twig', [
            'clients' => $clients
        ]);
    }
}