<?php

namespace App\Repository;

use App\Entity\Cow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cow>
 */
class CowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cow::class);
    }

    public function save(Cow $cow, bool $flush = false): void
    {
        $this->getEntityManager()->persist($cow);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Cow $cow, bool $flush = false): void
    {
        $this->getEntityManager()->remove($cow);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneAliveByCodeExcluding(string $code, ?int $id): ?Cow
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.code = :code')
            ->andWhere('c.slaughter IS NULL')
            ->setParameter('code', $code);

        if ($id) {
            $qb->andWhere('c.id != :id')
                ->setParameter('id', $id);
        }

        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function findEligibleForSlaughter(): QueryBuilder
    {
        $fiveYearsAgo = new \DateTime('-5 years');

        return $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->where('c.slaughter IS NULL')
            ->andWhere(
                'c.birthdate < :fiveYearsAgo OR ' .
                'c.milk < 40 OR ' .
                '(c.milk < 70 AND c.feed / 7 > 50) OR ' .
                'c.weight / 15 > 18'
            )
            ->setParameter('fiveYearsAgo', $fiveYearsAgo)
            ->orderBy('c.code', 'ASC');
    }

    public function findBySearch(?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->orderBy('c.code', 'ASC');

        if ($search) {
            $qb->where('c.code LIKE :search OR f.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb;
    }

    public function getTotalMilkPerWeek(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.milk), 0)')
            ->where('c.slaughter IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    public function getTotalFeedPerWeek(): float
    {
        $result = $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.feed), 0)')
            ->where('c.slaughter IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $result;
    }

    public function countYoungHighFeedCows(): int
    {
        $oneYearAgo = new \DateTime('-1 year');

        $result = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.slaughter IS NULL')
            ->andWhere('c.birthdate >= :oneYearAgo')
            ->andWhere('c.feed > 500')
            ->setParameter('oneYearAgo', $oneYearAgo)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    public function findSlaughtered(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->where('c.slaughter IS NOT NULL')
            ->orderBy('c.slaughter', 'DESC');
    }
}
