<?php 

namespace App\Service;

use App\Repository\StatisticRepository;

class StatisticService {

    private StatisticRepository $statisticsRepository;
    
    public function __construct(?StatisticRepository $statisticsRepository = null){
        $this->statisticsRepository = $statisticsRepository ?? new StatisticRepository();
    }

    public function getStatistics(int $limit, ?string $dateFrom = null, ?string $dateTo = null){
        if(!$dateFrom){
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
        }

        if(!$dateTo){
            $dateTo = date('Y-m-d');
        }

        $dateTo = date('Y-m-d 23:59:59', strtotime($dateTo));

        return [
            'topClientsByBalance' => $this->statisticsRepository->getTopClientsByBalance($limit, $dateFrom, $dateTo),
            'topClientsByVolume' => $this->statisticsRepository->getTopClientsByVolume($limit, $dateFrom, $dateTo),
        ];
    }
}