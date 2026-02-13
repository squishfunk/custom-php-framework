<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Service\ClientService;
use App\Service\TransactionService;
use App\Dto\TransactionDto;

class TransactionController extends Controller
{
    private TransactionService $transactionService;
    private ClientService $clientService;

    public function __construct()
    {
        $this->transactionService = new TransactionService();
        $this->clientService = new ClientService();
    }

    public function create()
    {
        $clientId = $_GET['client_id'] ?? null;

        if (!$clientId) {
            return new Response('Client ID missing', 400);
        }

        $client = $this->clientService->getClient((int) $clientId);
        if (!$client) {
            return new Response('Client not found', 404);
        }

        return $this->render('transaction/create.html.twig', [
            'client_name' => $client->getName(),
            'client_id' => $client->getId()
        ]);
    }

    public function store(Request $request)
    {
        $clientId = $request->input('client_id');

        $data = $this->validate($request, [
            'type' => 'required|in:deposit,earning,expense',
            'amount' => 'required|numeric',
            'description' => 'max:255',
            'date' => 'required|date'
        ]);

        $dto = new TransactionDto(
            (int) $clientId,
            $data['type'],
            (float) $data['amount'],
            $data['description'] ?? null,
            $data['date']
        );

        try {
            $this->transactionService->addTransaction($dto);
            $this->redirect('/clients/' . $clientId);
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage());
        }
    }
}
