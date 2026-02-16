<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Service\ClientService;
use App\Service\TransactionService;
use App\Dto\TransactionDto;
use App\Core\Exception\HttpException;
use App\Exception\ClientNotFoundException;
use App\Exception\InsufficientBalanceException;

class TransactionController extends Controller
{
    private TransactionService $transactionService;
    private ClientService $clientService;

    public function __construct(
        ?TransactionService $transactionService = null,
        ?ClientService $clientService = null
    ) {
        $this->transactionService = $transactionService ?? new TransactionService();
        $this->clientService = $clientService ?? new ClientService();
    }

    public function create(Request $request): Response
    {
        $clientId = $request->input('client_id');

        if (!$clientId) {
            return new Response('Client ID missing', 400);
        }

        try {
            $client = $this->clientService->getClient((int) $clientId);
        } catch (ClientNotFoundException $e) {
            throw new HttpException($e->getMessage(), 404);
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
            'type' => 'required',
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
            return $this->redirect('/clients/' . $clientId);
        } catch (ClientNotFoundException $e) {
            throw new HttpException($e->getMessage(), 404);
        } catch (InsufficientBalanceException $e) {
            $client = $this->clientService->getClient((int) $clientId);
            return $this->render('transaction/create.html.twig', [
                'client_name' => $client->getName(),
                'client_id' => $client->getId(),
                'error' => $e->getMessage(),
                'old' => $data
            ], 400);
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage());
        }
    }
}
