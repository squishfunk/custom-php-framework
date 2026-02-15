<?php 

namespace App\Service;

use App\Repository\StatisticRepository;

class StatisticService {

    private StatisticRepository $statisticsRepository;
    
    public function __construct(?StatisticRepository $statisticsRepository = null){
        $this->statisticsRepository = $statisticsRepository ?? new StatisticRepository();
    }

    public function getStatistics(int $limit, ?string $dateFrom = null, ?string $dateTo = null, bool $allTime = false){
        if(!$allTime){
            if(!$dateFrom){
                $dateFrom = date('Y-m-d', strtotime('-7 days'));
            }

            if(!$dateTo){
                $dateTo = date('Y-m-d');
            }

            $dateTo = date('Y-m-d 23:59:59', strtotime($dateTo));
        }

        return [
            'topClientsByBalance' => $this->statisticsRepository->getTopClientsByBalance($limit, $dateFrom, $dateTo),
            'topClientsByVolume' => $this->statisticsRepository->getTopClientsByVolume($limit, $dateFrom, $dateTo),
            'transactionTypeDistribution' => $this->statisticsRepository->getTransactionTypeDistribution($dateFrom, $dateTo),
            'dailyTransactionTrend' => $this->statisticsRepository->getDailyTransactionTrend($dateFrom, $dateTo),
            'totalMarketCap' => $this->statisticsRepository->getTotalMarketCap($dateFrom, $dateTo),
            'capitalDistribution' => $this->statisticsRepository->getCapitalDistribution($limit),
            'query' => ['date_from' => $dateFrom, 'date_to' => $dateTo],
        ];
    }
}