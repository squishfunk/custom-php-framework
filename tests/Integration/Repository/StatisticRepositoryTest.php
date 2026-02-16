<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Core\Database;
use App\Entity\Client;
use App\Entity\Transaction;
use App\Repository\ClientRepository;
use App\Repository\StatisticRepository;
use App\Repository\TransactionRepository;
use PHPUnit\Framework\TestCase;

class StatisticRepositoryTest extends TestCase
{
    private StatisticRepository $statisticRepository;
    private ClientRepository $clientRepository;
    private TransactionRepository $transactionRepository;

    protected function setUp(): void
    {
        Database::getConnection(true);
        $this->statisticRepository = new StatisticRepository();
        $this->clientRepository = new ClientRepository();
        $this->transactionRepository = new TransactionRepository();
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

    public function testGetTopClientsByBalance(): void
    {
        $client1 = Client::create('Rich Client', 'rich@example.com', 10000.0);
        $client2 = Client::create('Poor Client', 'poor@example.com', 1000.0);
        $client3 = Client::create('Middle Client', 'middle@example.com', 5000.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);

        $topClients = $this->statisticRepository->getTopClientsByBalance(3, null, null);

        $this->assertCount(3, $topClients);
        $this->assertEquals('Rich Client', $topClients[0]['name']);
        $this->assertEquals(10000.0, (float) $topClients[0]['balance']);
        $this->assertEquals('Middle Client', $topClients[1]['name']);
        $this->assertEquals('Poor Client', $topClients[2]['name']);
    }

    public function testGetTopClientsByBalanceWithDateRange(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 5000.0);
        $this->clientRepository->save($client);

        $dateFrom = date('Y-m-d');
        $dateTo = date('Y-m-d 23:59:59');

        $topClients = $this->statisticRepository->getTopClientsByBalance(5, $dateFrom, $dateTo);

        $this->assertCount(1, $topClients);
        $this->assertEquals('Test Client', $topClients[0]['name']);
    }

    public function testGetTopClientsByBalanceReturnsEmptyArrayWhenNoClients(): void
    {
        $topClients = $this->statisticRepository->getTopClientsByBalance(10, null, null);
        $this->assertEmpty($topClients);
    }

    public function testGetTopClientsByVolume(): void
    {
        $client1 = Client::create('High Volume', 'high@example.com', 1000.0);
        $client2 = Client::create('Low Volume', 'low@example.com', 1000.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);

        $transaction1 = Transaction::create($client1->getId(), 'earning', 5000.0, 'Big transaction', '2023-01-15');
        $transaction2 = Transaction::create($client2->getId(), 'earning', 500.0, 'Small transaction', '2023-01-15');
        $transaction3 = Transaction::create($client1->getId(), 'expense', 1000.0, 'Expense', '2023-01-16');
        
        $this->transactionRepository->save($transaction1);
        $this->transactionRepository->save($transaction2);
        $this->transactionRepository->save($transaction3);

        $topClients = $this->statisticRepository->getTopClientsByVolume(2, '2023-01-01', '2023-12-31');

        $this->assertCount(2, $topClients);
        $this->assertEquals('High Volume', $topClients[0]['name']);
        $this->assertEquals(6000.0, (float) $topClients[0]['volume']);
        $this->assertEquals('Low Volume', $topClients[1]['name']);
        $this->assertEquals(500.0, (float) $topClients[1]['volume']);
    }

    public function testGetTopClientsByVolumeReturnsEmptyArrayWhenNoTransactions(): void
    {
        $topClients = $this->statisticRepository->getTopClientsByVolume(10, null, null);
        $this->assertEmpty($topClients);
    }

    public function testGetTransactionTypeDistribution(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 1000.0);
        $this->clientRepository->save($client);

        $earning1 = Transaction::create($client->getId(), 'earning', 1000.0, 'Salary', '2023-01-15');
        $earning2 = Transaction::create($client->getId(), 'earning', 500.0, 'Bonus', '2023-01-16');
        $expense1 = Transaction::create($client->getId(), 'expense', 300.0, 'Rent', '2023-01-15');
        $expense2 = Transaction::create($client->getId(), 'expense', 200.0, 'Food', '2023-01-16');
        
        $this->transactionRepository->save($earning1);
        $this->transactionRepository->save($earning2);
        $this->transactionRepository->save($expense1);
        $this->transactionRepository->save($expense2);

        $distribution = $this->statisticRepository->getTransactionTypeDistribution('2023-01-01', '2023-12-31');

        $this->assertCount(2, $distribution);

        $earningData = array_filter($distribution, fn($item) => $item['type'] === 'earning');
        $expenseData = array_filter($distribution, fn($item) => $item['type'] === 'expense');

        $this->assertEquals(1500.0, (float) array_values($earningData)[0]['total']);
        $this->assertEquals(2, (int) array_values($earningData)[0]['count']);
        $this->assertEquals(500.0, (float) array_values($expenseData)[0]['total']);
        $this->assertEquals(2, (int) array_values($expenseData)[0]['count']);
    }

    public function testGetTransactionTypeDistributionReturnsEmptyArrayWhenNoTransactions(): void
    {
        $distribution = $this->statisticRepository->getTransactionTypeDistribution(null, null);
        $this->assertEmpty($distribution);
    }

    public function testGetDailyTransactionTrend(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 1000.0);
        $this->clientRepository->save($client);

        $transaction1 = Transaction::create($client->getId(), 'earning', 1000.0, 'Day 1', '2023-01-01');
        $transaction2 = Transaction::create($client->getId(), 'expense', 200.0, 'Day 1 expense', '2023-01-01');
        $transaction3 = Transaction::create($client->getId(), 'earning', 500.0, 'Day 2', '2023-01-02');
        
        $this->transactionRepository->save($transaction1);
        $this->transactionRepository->save($transaction2);
        $this->transactionRepository->save($transaction3);

        $trend = $this->statisticRepository->getDailyTransactionTrend('2023-01-01', '2023-01-31');

        $this->assertCount(2, $trend);
        
        $day1 = array_filter($trend, fn($item) => $item['date'] === '2023-01-01');
        $day2 = array_filter($trend, fn($item) => $item['date'] === '2023-01-02');

        $this->assertEquals(1200.0, (float) array_values($day1)[0]['total']);
        $this->assertEquals(2, (int) array_values($day1)[0]['count']);
        $this->assertEquals(500.0, (float) array_values($day2)[0]['total']);
        $this->assertEquals(1, (int) array_values($day2)[0]['count']);
    }

    public function testGetDailyTransactionTrendReturnsEmptyArrayWhenNoTransactions(): void
    {
        $trend = $this->statisticRepository->getDailyTransactionTrend(null, null);
        $this->assertEmpty($trend);
    }

    public function testGetTotalMarketCap(): void
    {
        $client = Client::create('Test Client', 'test@example.com', 0.0);
        $this->clientRepository->save($client);

        $transaction1 = Transaction::create($client->getId(), 'earning', 1000.0, 'Income', '2023-01-01');
        $transaction2 = Transaction::create($client->getId(), 'expense', 300.0, 'Expense', '2023-01-02');
        $transaction3 = Transaction::create($client->getId(), 'earning', 500.0, 'Income', '2023-01-03');
        
        $this->transactionRepository->save($transaction1);
        $this->transactionRepository->save($transaction2);
        $this->transactionRepository->save($transaction3);

        $marketCap = $this->statisticRepository->getTotalMarketCap('2023-01-01', '2023-01-31');

        $this->assertCount(3, $marketCap);
        
        $this->assertEquals('2023-01-01', $marketCap[0]['day']);
        $this->assertEquals(1000.0, (float) $marketCap[0]['total_company_value']);
        $this->assertEquals('2023-01-02', $marketCap[1]['day']);
        $this->assertEquals(700.0, (float) $marketCap[1]['total_company_value']);
        $this->assertEquals('2023-01-03', $marketCap[2]['day']);
        $this->assertEquals(1200.0, (float) $marketCap[2]['total_company_value']);
    }

    public function testGetTotalMarketCapReturnsEmptyArrayWhenNoTransactions(): void
    {
        $marketCap = $this->statisticRepository->getTotalMarketCap(null, null);
        $this->assertEmpty($marketCap);
    }

    public function testGetCapitalDistribution(): void
    {
        $client1 = Client::create('Rich', 'rich@example.com', 10000.0);
        $client2 = Client::create('Middle', 'middle@example.com', 5000.0);
        $client3 = Client::create('Poor', 'poor@example.com', 5000.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);

        $distribution = $this->statisticRepository->getCapitalDistribution(3);

        $this->assertCount(3, $distribution);
        
        $totalBalance = 20000.0;
        $this->assertEqualsWithDelta(50.0, (float) $distribution[0]['percentage'], 0.1);
        $this->assertEqualsWithDelta(25.0, (float) $distribution[1]['percentage'], 0.1);
        $this->assertEqualsWithDelta(25.0, (float) $distribution[2]['percentage'], 0.1);
        
        $this->assertEquals(10000.0, (float) $distribution[0]['balance']);
        $this->assertEquals('Rich', $distribution[0]['name']);
    }

    public function testGetCapitalDistributionIgnoresZeroAndNegativeBalance(): void
    {
        $client1 = Client::create('Rich', 'rich@example.com', 1000.0);
        $client2 = Client::create('Zero', 'zero@example.com', 0.0);
        $client3 = Client::create('Negative', 'negative@example.com', -500.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);
        $this->clientRepository->save($client3);

        $distribution = $this->statisticRepository->getCapitalDistribution(10);

        $this->assertCount(1, $distribution);
        $this->assertEquals('Rich', $distribution[0]['name']);
        $this->assertEquals(100.0, (float) $distribution[0]['percentage']);
    }

    public function testGetCapitalDistributionReturnsEmptyArrayWhenNoPositiveBalances(): void
    {
        $client1 = Client::create('Zero', 'zero@example.com', 0.0);
        $client2 = Client::create('Negative', 'negative@example.com', -100.0);
        
        $this->clientRepository->save($client1);
        $this->clientRepository->save($client2);

        $distribution = $this->statisticRepository->getCapitalDistribution(10);
        $this->assertEmpty($distribution);
    }

    public function testGetCapitalDistributionWithLimit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $client = Client::create("Client $i", "client$i@example.com", (float) ($i * 100));
            $this->clientRepository->save($client);
        }

        $distribution = $this->statisticRepository->getCapitalDistribution(3);

        $this->assertCount(3, $distribution);
    }
}
