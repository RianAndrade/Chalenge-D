<?php

namespace App\Tests\Repository;

use App\Repository\FarmRepository;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FarmRepositoryTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private FarmRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->truncateAll();
        $this->repository = static::getContainer()->get(FarmRepository::class);
    }

    public function testFindByFiltersNoFilters(): void
    {
        $this->createFarm('Fazenda A');
        $this->createFarm('Fazenda B');

        $results = $this->repository->findByFilters([])->getQuery()->getResult();

        self::assertCount(2, $results);
    }

    public function testFindByFiltersSearchByName(): void
    {
        $this->createFarm('Fazenda Sol');
        $this->createFarm('Fazenda Lua');

        $results = $this->repository->findByFilters(['search' => 'Sol'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('Fazenda Sol', $results[0]->getName());
    }

    public function testFindByFiltersSearchByManager(): void
    {
        $this->createFarm('Fazenda A', manager: 'Carlos');
        $this->createFarm('Fazenda B', manager: 'Maria');

        $results = $this->repository->findByFilters(['search' => 'Carlos'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('Carlos', $results[0]->getManager());
    }

    public function testFindByFiltersSizeRange(): void
    {
        $this->createFarm('Fazenda A', size: 5.0);
        $this->createFarm('Fazenda B', size: 10.0);
        $this->createFarm('Fazenda C', size: 20.0);

        $results = $this->repository->findByFilters(['size_min' => 8, 'size_max' => 15])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('Fazenda B', $results[0]->getName());
    }

    public function testFindByFiltersVeterinarianId(): void
    {
        $farm = $this->createFarm('Fazenda A');
        $vet = $this->createVeterinarian('Dr. Ana', 'CRMV-100');

        $em = $this->getEntityManager();
        $farm->addVeterinarian($vet);
        $em->flush();

        $this->createFarm('Fazenda B');

        $results = $this->repository->findByFilters(['veterinarian' => $vet->getId()])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('Fazenda A', $results[0]->getName());
    }

    public function testFindByManager(): void
    {
        $this->createFarm('Fazenda A', manager: 'Carlos');
        $this->createFarm('Fazenda B', manager: 'Maria');

        $results = $this->repository->findByManager('Carlos');

        self::assertCount(1, $results);
        self::assertSame('Carlos', $results[0]->getManager());
    }

    public function testFindByVeterinarian(): void
    {
        $farm = $this->createFarm('Fazenda A');
        $vet = $this->createVeterinarian('Dr. Ana', 'CRMV-200');

        $em = $this->getEntityManager();
        $farm->addVeterinarian($vet);
        $em->flush();

        $results = $this->repository->findByVeterinarian($vet->getId());

        self::assertCount(1, $results);
        self::assertSame('Fazenda A', $results[0]->getName());
    }
}
