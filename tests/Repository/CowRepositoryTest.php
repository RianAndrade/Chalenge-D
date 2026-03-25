<?php

namespace App\Tests\Repository;

use App\Repository\CowRepository;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CowRepositoryTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private CowRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->truncateAll();
        $this->repository = static::getContainer()->get(CowRepository::class);
    }

    public function testFindByFiltersNoFilters(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm);
        $this->createCow('COW-002', $farm);
        $this->createCow('COW-003', $farm);

        $results = $this->repository->findByFilters([])->getQuery()->getResult();

        self::assertCount(3, $results);
    }

    public function testFindByFiltersSearchByCode(): void
    {
        $farm = $this->createFarm();
        $this->createCow('ABC-001', $farm);
        $this->createCow('XYZ-002', $farm);

        $results = $this->repository->findByFilters(['search' => 'ABC'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('ABC-001', $results[0]->getCode());
    }

    public function testFindByFiltersSearchByFarmName(): void
    {
        $farm1 = $this->createFarm('Fazenda Sol');
        $farm2 = $this->createFarm('Fazenda Lua');
        $this->createCow('COW-001', $farm1);
        $this->createCow('COW-002', $farm2);

        $results = $this->repository->findByFilters(['search' => 'Fazenda Sol'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('COW-001', $results[0]->getCode());
    }

    public function testFindByFiltersByFarmId(): void
    {
        $farm1 = $this->createFarm('Fazenda A');
        $farm2 = $this->createFarm('Fazenda B');
        $this->createCow('COW-001', $farm1);
        $this->createCow('COW-002', $farm2);

        $results = $this->repository->findByFilters(['farm' => $farm1->getId()])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('COW-001', $results[0]->getCode());
    }

    public function testFindByFiltersStatusAlive(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm, slaughtered: false);
        $this->createCow('COW-002', $farm, slaughtered: true);

        $results = $this->repository->findByFilters(['status' => 'alive'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('COW-001', $results[0]->getCode());
    }

    public function testFindByFiltersStatusSlaughtered(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm, slaughtered: false);
        $this->createCow('COW-002', $farm, slaughtered: true);

        $results = $this->repository->findByFilters(['status' => 'slaughtered'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('COW-002', $results[0]->getCode());
    }

    public function testFindByFiltersMilkRange(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm, milk: 50.0);
        $this->createCow('COW-002', $farm, milk: 100.0);
        $this->createCow('COW-003', $farm, milk: 150.0);

        $results = $this->repository->findByFilters(['milk_min' => '60', 'milk_max' => '120'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('COW-002', $results[0]->getCode());
    }

    public function testFindByFiltersWeightRange(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm, weight: 100.0);
        $this->createCow('COW-002', $farm, weight: 200.0);
        $this->createCow('COW-003', $farm, weight: 300.0);

        $results = $this->repository->findByFilters(['weight_min' => '150', 'weight_max' => '250'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('COW-002', $results[0]->getCode());
    }

    public function testFindEligibleForSlaughter(): void
    {
        $farm = $this->createFarm();

        // age > 5 years
        $this->createCow('OLD-001', $farm, milk: 100.0, feed: 50.0, weight: 200.0, birthdate: '-6 years');

        // milk < 40
        $this->createCow('LOW-MILK', $farm, milk: 30.0, feed: 50.0, weight: 200.0);

        // milk < 70 AND daily feed > 50 (feed=400/7=57.1)
        $this->createCow('HIGH-FEED', $farm, milk: 60.0, feed: 400.0, weight: 200.0);

        // weight > 18@ (280/15=18.67)
        $this->createCow('HEAVY', $farm, milk: 100.0, feed: 50.0, weight: 280.0);

        // healthy cow - should NOT appear
        $this->createCow('HEALTHY', $farm, milk: 100.0, feed: 50.0, weight: 200.0, birthdate: '-2 years');

        // slaughtered matching criteria - should NOT appear
        $this->createCow('DEAD', $farm, milk: 30.0, feed: 50.0, weight: 200.0, slaughtered: true);

        $results = $this->repository->findEligibleForSlaughter()->getQuery()->getResult();

        self::assertCount(4, $results);
    }

    public function testFindSlaughtered(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm, slaughtered: true);
        $this->createCow('COW-002', $farm, slaughtered: true);
        $this->createCow('COW-003', $farm, slaughtered: false);

        $results = $this->repository->findSlaughtered()->getQuery()->getResult();

        self::assertCount(2, $results);
    }

    public function testFindOneAliveByCodeExcluding(): void
    {
        $farm = $this->createFarm();
        $aliveCow = $this->createCow('X', $farm);

        $result = $this->repository->findOneAliveByCodeExcluding('X', $aliveCow->getId());
        self::assertNull($result);

        $result = $this->repository->findOneAliveByCodeExcluding('X', null);
        self::assertNotNull($result);
        self::assertSame('X', $result->getCode());

        $this->createCow('X', $farm, slaughtered: true);

        $result = $this->repository->findOneAliveByCodeExcluding('X', null);
        self::assertNotNull($result);
        self::assertTrue($result->isAlive());
    }

    public function testGetTotalMilkPerWeek(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm, milk: 100.0);
        $this->createCow('COW-002', $farm, milk: 200.0);
        $this->createCow('COW-003', $farm, milk: 300.0);
        $this->createCow('COW-004', $farm, milk: 500.0, slaughtered: true);

        $total = $this->repository->getTotalMilkPerWeek();

        self::assertEquals(600.0, $total);
    }

    public function testGetTotalFeedPerWeek(): void
    {
        $farm = $this->createFarm();
        $this->createCow('COW-001', $farm, feed: 50.0);
        $this->createCow('COW-002', $farm, feed: 100.0);
        $this->createCow('COW-003', $farm, feed: 150.0);
        $this->createCow('COW-004', $farm, feed: 500.0, slaughtered: true);

        $total = $this->repository->getTotalFeedPerWeek();

        self::assertEquals(300.0, $total);
    }

    public function testCountYoungHighFeedCows(): void
    {
        $farm = $this->createFarm();

        // Young + high feed - matches
        $this->createCow('YOUNG-HIGH', $farm, feed: 600.0, birthdate: '-6 months');

        // Old + high feed - too old
        $this->createCow('OLD-HIGH', $farm, feed: 600.0, birthdate: '-2 years');

        // Young + low feed - too low
        $this->createCow('YOUNG-LOW', $farm, feed: 400.0, birthdate: '-6 months');

        $count = $this->repository->countYoungHighFeedCows();

        self::assertSame(1, $count);
    }

    public function testFindTop10MilkProducers(): void
    {
        $farm = $this->createFarm();

        for ($i = 1; $i <= 12; $i++) {
            $this->createCow(sprintf('COW-%03d', $i), $farm, milk: $i * 10.0);
        }

        $results = $this->repository->findTop10MilkProducers();

        self::assertCount(10, $results);
        self::assertEquals(120.0, $results[0]->getMilk());
    }

    public function testFindTop10FeedConsumersOverOneYear(): void
    {
        $farm = $this->createFarm();

        // Over 1 year old with varying feed
        $this->createCow('OLD-001', $farm, feed: 500.0, birthdate: '-2 years');
        $this->createCow('OLD-002', $farm, feed: 400.0, birthdate: '-3 years');
        $this->createCow('OLD-003', $farm, feed: 300.0, birthdate: '-2 years');
        $this->createCow('OLD-004', $farm, feed: 200.0, birthdate: '-4 years');
        $this->createCow('OLD-005', $farm, feed: 100.0, birthdate: '-5 years');

        // Under 1 year old with high feed - should NOT appear
        $this->createCow('YOUNG-001', $farm, feed: 900.0, birthdate: '-6 months');
        $this->createCow('YOUNG-002', $farm, feed: 800.0, birthdate: '-3 months');

        $results = $this->repository->findTop10FeedConsumersOverOneYear();

        self::assertCount(5, $results);
        self::assertEquals(500.0, $results[0]->getFeed());

        foreach ($results as $cow) {
            self::assertStringStartsWith('OLD-', $cow->getCode());
        }
    }

    public function testGetTotalFeedPerFarm(): void
    {
        $farm1 = $this->createFarm('Fazenda A');
        $farm2 = $this->createFarm('Fazenda B');

        $this->createCow('COW-001', $farm1, feed: 100.0);
        $this->createCow('COW-002', $farm1, feed: 200.0);
        $this->createCow('COW-003', $farm2, feed: 300.0);

        $results = $this->repository->getTotalFeedPerFarm();

        self::assertCount(2, $results);

        $feedByFarm = [];
        foreach ($results as $row) {
            $feedByFarm[$row['farmName']] = (float) $row['totalFeed'];
        }

        self::assertEquals(300.0, $feedByFarm['Fazenda A']);
        self::assertEquals(300.0, $feedByFarm['Fazenda B']);
    }

    public function testFindMilkReport(): void
    {
        $farm1 = $this->createFarm('Fazenda A');
        $farm2 = $this->createFarm('Fazenda B');

        $this->createCow('COW-001', $farm1, milk: 50.0);
        $this->createCow('COW-002', $farm1, milk: 150.0);
        $this->createCow('COW-003', $farm2, milk: 200.0);

        // Filter by farm
        $results = $this->repository->findMilkReport($farm1->getId(), null, null)->getQuery()->getResult();
        self::assertCount(2, $results);

        // Filter by milk range
        $results = $this->repository->findMilkReport(null, 100.0, 180.0)->getQuery()->getResult();
        self::assertCount(1, $results);
        self::assertSame('COW-002', $results[0]->getCode());
    }

    public function testFindFeedReport(): void
    {
        $farm1 = $this->createFarm('Fazenda A');
        $farm2 = $this->createFarm('Fazenda B');

        $this->createCow('COW-001', $farm1, feed: 50.0);
        $this->createCow('COW-002', $farm1, feed: 150.0);
        $this->createCow('COW-003', $farm2, feed: 200.0);

        // Filter by farm
        $results = $this->repository->findFeedReport($farm1->getId(), null, null)->getQuery()->getResult();
        self::assertCount(2, $results);

        // Filter by feed range
        $results = $this->repository->findFeedReport(null, 100.0, 180.0)->getQuery()->getResult();
        self::assertCount(1, $results);
        self::assertSame('COW-002', $results[0]->getCode());
    }

    public function testFindYoungHighFeedReport(): void
    {
        $farm1 = $this->createFarm('Fazenda A');
        $farm2 = $this->createFarm('Fazenda B');

        $this->createCow('COW-001', $farm1, feed: 600.0, birthdate: '-6 months');
        $this->createCow('COW-002', $farm1, feed: 700.0, birthdate: '-3 months');
        $this->createCow('COW-003', $farm2, feed: 800.0, birthdate: '-4 months');

        // No filters
        $results = $this->repository->findYoungHighFeedReport(null, null)->getQuery()->getResult();
        self::assertCount(3, $results);

        // Filter by farm
        $results = $this->repository->findYoungHighFeedReport($farm1->getId(), null)->getQuery()->getResult();
        self::assertCount(2, $results);

        // Filter by feedMin
        $results = $this->repository->findYoungHighFeedReport(null, 650.0)->getQuery()->getResult();
        self::assertCount(2, $results);
    }
}
