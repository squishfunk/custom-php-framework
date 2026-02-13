<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Service\ClientService;
use App\Service\TransactionService;

class StatisticsController extends Controller
{
    private ClientService $clientService;
    private TransactionService $transactionService;

    public function __construct()
    {
        $this->clientService = new ClientService();
        $this->transactionService = new TransactionService();
    }

    public function index()
    {
        $topBalance = array_map(function ($client) {
            return [
                'name' => $client->getName(),
                'balance' => $client->getBalance()
            ];
        }, $this->clientService->getTopClientsByBalance(10));

        $topVolume = $this->transactionService->getTopClientsByVolume(10);

        return $this->render('statistics/index.html.twig', [
            'topBalance' => $topBalance,
            'topVolume' => $topVolume
        ]);
    }
}
