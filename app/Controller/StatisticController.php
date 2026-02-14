<?php

namespace App\Controller;

use App\Core\Controller;
use App\Core\Request;
use App\Service\StatisticService;

class StatisticController extends Controller
{
    private StatisticService $statisticService;

    public function __construct(?StatisticService $statisticService = null)
    {
        $this->statisticService = $statisticService ?? new StatisticService();
    }

    public function index(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $data = $this->statisticService->getStatistics(10, $dateFrom, $dateTo);

        return $this->render('statistics/index.html.twig', $data);
    }
}
