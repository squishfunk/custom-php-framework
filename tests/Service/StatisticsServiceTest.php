<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Service\StatisticService;
use App\Repository\StatisticRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class StatisticsServiceTest extends TestCase
{
    private StatisticService $statisticsService;
    private $statisticsRepositoryMock;

    protected function setUp(): void
    {
        $this->statisticsRepositoryMock = $this->createMock(StatisticRepository::class);

        $this->statisticsService = new StatisticService(
            $this->statisticsRepositoryMock
        );
    }

    public function testGetStatistics(): void
    {
        $limit = 10;

        $clientsData = [
            ['id' => 1, 'name' => 'Rich Client', 'balance' => 10000.0],
            ['id' => 2, 'name' => 'Poor Client', 'balance' => 10.0],
        ];

        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTopClientsByBalance')
            ->with($limit)
            ->willReturn($clientsData);

        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTopClientsByVolume')
            ->with($limit)
            ->willReturn($clientsData);

        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTransactionTypeDistribution')
            ->willReturn([
                ['type' => 'earning', 'total' => 5000.0, 'count' => 50],
                ['type' => 'expense', 'total' => 2000.0, 'count' => 30],
            ]);

        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getDailyTransactionTrend')
            ->willReturn([
                ['date' => '2023-01-01', 'total' => 100.0, 'count' => 5],
                ['date' => '2023-01-02', 'total' => 200.0, 'count' => 8],
            ]);

        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTotalMarketCap')
            ->willReturn([
                ['day' => '2023-01-01', 'total_company_value' => 1000.0],
                ['day' => '2023-01-02', 'total_company_value' => 1500.0],
            ]);

        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getCapitalDistribution')
            ->with($limit)
            ->willReturn([
                ['id' => 1, 'name' => 'Rich Client', 'balance' => 10000.0, 'percentage' => 80.0],
                ['id' => 2, 'name' => 'Poor Client', 'balance' => 1000.0, 'percentage' => 20.0],
            ]);

        $data = $this->statisticsService->getStatistics($limit);

        $this->assertArrayHasKey('topClientsByBalance', $data);
        $this->assertArrayHasKey('topClientsByVolume', $data);
        $this->assertArrayHasKey('transactionTypeDistribution', $data);
        $this->assertArrayHasKey('dailyTransactionTrend', $data);
        $this->assertArrayHasKey('totalMarketCap', $data);
        $this->assertArrayHasKey('capitalDistribution', $data);
        $this->assertCount(2, $data['topClientsByBalance']);
        $this->assertCount(2, $data['topClientsByVolume']);
    }

    public function testGetStatisticsWithSpecificDates(): void
    {
        $limit = 5;
        $dateFrom = '2023-01-01';
        $dateTo = '2023-01-31';

        $expectedDateTo = date('Y-m-d 23:59:59', strtotime($dateTo));
        $clientsData = [];
        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTopClientsByBalance')
            ->with($limit, $dateFrom, $expectedDateTo)
            ->willReturn($clientsData);
        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTopClientsByVolume')
            ->with($limit, $dateFrom, $expectedDateTo)
            ->willReturn($clientsData);
        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTransactionTypeDistribution')
            ->with($dateFrom, $expectedDateTo)
            ->willReturn([]);
        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getDailyTransactionTrend')
            ->with($dateFrom, $expectedDateTo)
            ->willReturn([]);
        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getTotalMarketCap')
            ->with($dateFrom, $expectedDateTo)
            ->willReturn([]);
        $this->statisticsRepositoryMock
            ->expects($this->once())
            ->method('getCapitalDistribution')
            ->with($limit, $dateFrom, $expectedDateTo)
            ->willReturn([]);
        $this->statisticsService->getStatistics($limit, $dateFrom, $dateTo);
    }
}
