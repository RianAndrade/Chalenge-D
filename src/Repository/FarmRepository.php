<?php

namespace App\Repository;

use App\Entity\Farm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Farm>
 */
class FarmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Farm::class);
    }

    public function save(Farm $farm, bool $flush = false): void
    {
        $this->getEntityManager()->persist($farm);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Farm $farm, bool $flush = false): void
    {
        $this->getEntityManager()->remove($farm);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByFilters(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('f')
            ->leftJoin('f.veterinarians', 'v')
            ->addSelect('v')
            ->orderBy('f.name', 'ASC');

        if (isset($filters['search']) && $filters['search'] !== '') {
            $qb->andWhere('f.name LIKE :search OR f.manager LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['size_min']) && $filters['size_min'] !== '') {
            $qb->andWhere('f.size >= :sizeMin')
                ->setParameter('sizeMin', (float) $filters['size_min']);
        }

        if (isset($filters['size_max']) && $filters['size_max'] !== '') {
            $qb->andWhere('f.size <= :sizeMax')
                ->setParameter('sizeMax', (float) $filters['size_max']);
        }

        if (isset($filters['veterinarian']) && $filters['veterinarian'] !== '') {
            $qb->andWhere('v.id = :vetId')
                ->setParameter('vetId', $filters['veterinarian']);
        }

        return $qb;
    }

    public function findByManager(string $manager): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.manager LIKE :manager')
            ->setParameter('manager', '%' . $manager . '%')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByVeterinarian(int $veterinarianId): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.veterinarians', 'v')
            ->where('v.id = :veterinarianId')
            ->setParameter('veterinarianId', $veterinarianId)
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
