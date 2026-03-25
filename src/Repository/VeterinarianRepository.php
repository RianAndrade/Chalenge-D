<?php

namespace App\Repository;

use App\Entity\Veterinarian;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Veterinarian>
 */
class VeterinarianRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Veterinarian::class);
    }

    public function save(Veterinarian $veterinarian, bool $flush = false): void
    {
        $this->getEntityManager()->persist($veterinarian);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Veterinarian $veterinarian, bool $flush = false): void
    {
        $this->getEntityManager()->remove($veterinarian);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBySearch(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('v')
            ->orderBy('v.name', 'ASC');

        if ($search) {
            $qb->where('v.name LIKE :search OR v.crmv LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb;
    }

    public function findByCrmv(string $crmv): ?Veterinarian
    {
        return $this->findOneBy(['crmv' => $crmv]);
    }
}
