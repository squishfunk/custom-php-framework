<?php

declare(strict_types=1);

namespace Tests\Integration\Service;

use App\Core\Database;
use App\Dto\TransactionDto;
use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Repository\StatisticRepository;
use App\Repository\TransactionRepository;
use App\Service\StatisticService;
use App\Service\TransactionService;
use PHPUnit\Framework\TestCase;

class StatisticServiceIntegrationTest extends TestCase
{
    private StatisticService $statisticService;
    private ClientRepository $clientRepository;
    private TransactionRepository $transactionRepository;
    private TransactionService $transactionService;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->clientRepository = new ClientRepository();
        $this->transactionRepository = new TransactionRepository();
        $this->transactionService = new TransactionService(
            $this->transactionRepository,
            $this->clientRepository
        );
        $this->statisticService = new StatisticService(
            new StatisticRepository()
        );

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
    }

    private function cleanDatabase(): void
    {
        $db = Database::getConnection(true);
        $db->exec('SET FOREIGN_KEY_CHECKS = 0');
        $db->exec('DELETE FROM transactions');
        $db->exec('DELETE FROM clients');
        $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testGetStatisticsReturnsAllRequiredKeys(): void
    {
        $statistics = $this->statisticService->getStatistics(10);

        $this->assertArrayHasKey('topClientsByBalance', $statistics);
        $this->assertArrayHasKey('topClientsByVolume', $statistics);
        $this->assertArrayHasKey('transactionTypeDistribution', $statistics);
        $this->assertArrayHasKey('dailyTransactionTrend', $statistics);
        $this->assertArrayHasKey('totalMarketCap', $statistics);
        $this->assertArrayHasKey('capitalDistribution', $statistics);
        $this->assertArrayHasKey('query', $statistics);
    }

    public function testGetStatisticsWithDateRange(): void
    {
        $dateFrom = '2023-01-01';
        $dateTo = '2023-12-31';
        
        $statistics = $this->statisticService->getStatistics(10, $dateFrom, $dateTo);

        $this->assertEquals($dateFrom, $statistics['query']['date_from']);
        $this->assertStringStartsWith($dateTo, $statistics['query']['date_to']);
    }

    public function testGetStatisticsWithDefaultDates(): void
    {
        $statistics = $this->statisticService->getStatistics(10);

        $this->assertArrayHasKey('date_from', $statistics['query']);
        $this->assertArrayHasKey('date_to', $statistics['query']);
        $this->assertNotNull($statistics['query']['date_from']);
        $this->assertNotNull($statistics['query']['date_to']);
    }

    public function testTopClientsByBalance(): void
    {
        $client1 = Client::create('Rich Client', 'rich@example.com', 10000.0);
        $client2 = Client::create('Middle Client', 'middle@example.com', 5000.0);
        $client3 = Client::create('Poor Client', 'poor@example.com', 1000.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);

        $statistics = $this->statisticService->getStatistics(3, null, null, true);

        $this->assertCount(3, $statistics['topClientsByBalance']);
        $this->assertEquals('Rich Client', $statistics['topClientsByBalance'][0]['name']);
        $this->assertEquals(10000.0, (float) $statistics['topClientsByBalance'][0]['balance']);
    }

    public function testTopClientsByVolume(): void
    {
        $client1 = Client::create('High Volume', 'high@example.com', 1000.0);
        $client2 = Client::create('Low Volume', 'low@example.com', 1000.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);

        $date = date('Y-m-d');
        
        $this->transactionService->addTransaction(
            new TransactionDto($client1->getId(), 'earning', 5000.0, 'Big earning', $date)
        );
        $this->transactionService->addTransaction(
            new TransactionDto($client2->getId(), 'earning', 500.0, 'Small earning', $date)
        );

        $statistics = $this->statisticService->getStatistics(2, $date, $date);

        $this->assertCount(2, $statistics['topClientsByVolume']);
        $this->assertEquals('High Volume', $statistics['topClientsByVolume'][0]['name']);
        $this->assertEquals(5000.0, (float) $statistics['topClientsByVolume'][0]['volume']);
    }

    public function testTransactionTypeDistribution(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 1000.0);
        $this->clientRepository->save($client);

        $date = date('Y-m-d');
        
        $this->transactionService->addTransaction(
            new TransactionDto($client->getId(), 'earning', 1000.0, 'Salary', $date)
        );
        $this->transactionService->addTransaction(
            new TransactionDto($client->getId(), 'expense', 300.0, 'Rent', $date)
        );
        $this->transactionService->addTransaction(
            new TransactionDto($client->getId(), 'expense', 200.0, 'Food', $date)
        );

        $statistics = $this->statisticService->getStatistics(10, $date, $date);

        $typeDistribution = $statistics['transactionTypeDistribution'];
        $this->assertCount(2, $typeDistribution);

        $earningData = array_filter($typeDistribution, fn($item) => $item['type'] === 'earning');
        $expenseData = array_filter($typeDistribution, fn($item) => $item['type'] === 'expense');

        $this->assertEquals(1000.0, (float) array_values($earningData)[0]['total']);
        $this->assertEquals(500.0, (float) array_values($expenseData)[0]['total']);
    }

    public function testDailyTransactionTrend(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 1000.0);
        $this->clientRepository->save($client);

        $date1 = '2023-01-01';
        $date2 = '2023-01-02';
        
        $this->transactionService->addTransaction(
            new TransactionDto($client->getId(), 'earning', 1000.0, 'Day 1', $date1)
        );
        $this->transactionService->addTransaction(
            new TransactionDto($client->getId(), 'earning', 500.0, 'Day 2', $date2)
        );

        $statistics = $this->statisticService->getStatistics(10, $date1, $date2);

        $this->assertGreaterThanOrEqual(2, count($statistics['dailyTransactionTrend']));
    }

    public function testTotalMarketCap(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 0.0);
        $this->clientRepository->save($client);

        $date = '2023-01-15';
        
        $this->transactionService->addTransaction(
            new TransactionDto($client->getId(), 'earning', 1000.0, 'Income', $date)
        );
        $this->transactionService->addTransaction(
            new TransactionDto($client->getId(), 'expense', 300.0, 'Expense', $date)
        );

        $statistics = $this->statisticService->getStatistics(10, $date, $date);

        $this->assertNotEmpty($statistics['totalMarketCap']);
        $marketCapData = $statistics['totalMarketCap'][0];
        $this->assertArrayHasKey('day', $marketCapData);
        $this->assertArrayHasKey('total_company_value', $marketCapData);
    }

    public function testCapitalDistribution(): void
    {
        $client1 = Client::create('Rich', 'rich@example.com', 10000.0);
        $client2 = Client::create('Middle', 'middle@example.com', 5000.0);
        $client3 = Client::create('Poor', 'poor@example.com', 5000.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);

        $statistics = $this->statisticService->getStatistics(3, null, null, true);

        $this->assertCount(3, $statistics['capitalDistribution']);
        
        $totalPercentage = 0;
        foreach ($statistics['capitalDistribution'] as $client) {
            $this->assertArrayHasKey('percentage', $client);
            $totalPercentage += (float) $client['percentage'];
        }
        $this->assertEqualsWithDelta(100.0, $totalPercentage, 0.1);
    }

    public function testEmptyStatisticsWhenNoData(): void
    {
        $statistics = $this->statisticService->getStatistics(10, null, null, true);

        $this->assertEmpty($statistics['topClientsByBalance']);
        $this->assertEmpty($statistics['topClientsByVolume']);
        $this->assertEmpty($statistics['transactionTypeDistribution']);
        $this->assertEmpty($statistics['dailyTransactionTrend']);
        $this->assertEmpty($statistics['totalMarketCap']);
        $this->assertEmpty($statistics['capitalDistribution']);
    }
}
