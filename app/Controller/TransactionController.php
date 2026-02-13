<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Service\ClientService;
use App\Service\TransactionService;

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
        // TODO not a good practice to use $_GET
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
        $clientId = (int) $request->input('client_id');
        $type = $request->input('type');
        $amount = (float) $request->input('amount');
        $description = $request->input('description');
        $date = $request->input('date');

        try {
            $this->transactionService->addTransaction($clientId, $type, $amount, $description, $date);
            $this->redirect('/client/' . $clientId);
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage());
        }
    }
}
