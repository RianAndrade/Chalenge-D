<?php

namespace App\Tests\Repository;

use App\Repository\VeterinarianRepository;
use App\Tests\DatabaseTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VeterinarianRepositoryTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private VeterinarianRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->truncateAll();
        $this->repository = static::getContainer()->get(VeterinarianRepository::class);
    }

    public function testFindByFiltersNoFilters(): void
    {
        $this->createVeterinarian('Dr. Ana', 'CRMV-001');
        $this->createVeterinarian('Dr. Carlos', 'CRMV-002');

        $results = $this->repository->findByFilters([])->getQuery()->getResult();

        self::assertCount(2, $results);
    }

    public function testFindByFiltersSearchByName(): void
    {
        $this->createVeterinarian('Dr. Ana', 'CRMV-001');
        $this->createVeterinarian('Dr. Carlos', 'CRMV-002');

        $results = $this->repository->findByFilters(['search' => 'Ana'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('Dr. Ana', $results[0]->getName());
    }

    public function testFindByFiltersSearchByCrmv(): void
    {
        $this->createVeterinarian('Dr. Ana', 'SP-12345');
        $this->createVeterinarian('Dr. Carlos', 'RJ-67890');

        $results = $this->repository->findByFilters(['search' => 'SP-12345'])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('SP-12345', $results[0]->getCrmv());
    }

    public function testFindByFiltersFarmId(): void
    {
        $farm = $this->createFarm('Fazenda A');
        $vet = $this->createVeterinarian('Dr. Ana', 'CRMV-001');
        $this->createVeterinarian('Dr. Carlos', 'CRMV-002');

        $em = $this->getEntityManager();
        $farm->addVeterinarian($vet);
        $em->flush();

        $results = $this->repository->findByFilters(['farm' => $farm->getId()])->getQuery()->getResult();

        self::assertCount(1, $results);
        self::assertSame('Dr. Ana', $results[0]->getName());
    }

    public function testFindByCrmv(): void
    {
        $this->createVeterinarian('Dr. Ana', 'SP-999');

        $result = $this->repository->findByCrmv('SP-999');

        self::assertNotNull($result);
        self::assertSame('SP-999', $result->getCrmv());
    }

    public function testFindByCrmvNotFound(): void
    {
        $result = $this->repository->findByCrmv('NONEXISTENT');

        self::assertNull($result);
    }
}
