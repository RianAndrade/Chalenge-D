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
            ->addSelect('f')
            ->where('c.slaughter IS NULL')
            ->andWhere(
                'c.birthdate < :fiveYearsAgo OR ' .
                'c.milk < 40 OR ' .
                '(c.milk < 70 AND (c.feed * 1.0) / 7 > 50) OR ' .
                '(c.weight * 1.0) / 15 > 18'
            )
            ->setParameter('fiveYearsAgo', $fiveYearsAgo)
            ->orderBy('c.code', 'ASC');
    }


    public function findByFilters(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->addSelect('f')
            ->orderBy('c.code', 'ASC');

        if (isset($filters['search']) && $filters['search'] !== '') {
            $qb->andWhere('c.code LIKE :search OR f.name LIKE :search')
                ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['farm']) && $filters['farm'] !== '') {
            $qb->andWhere('f.id = :farmId')
                ->setParameter('farmId', $filters['farm']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'alive') {
                $qb->andWhere('c.slaughter IS NULL');
            } elseif ($filters['status'] === 'slaughtered') {
                $qb->andWhere('c.slaughter IS NOT NULL');
            }
        }

        if (isset($filters['milk_min']) && $filters['milk_min'] !== '') {
            $qb->andWhere('c.milk >= :milkMin')
                ->setParameter('milkMin', (float) $filters['milk_min']);
        }

        if (isset($filters['milk_max']) && $filters['milk_max'] !== '') {
            $qb->andWhere('c.milk <= :milkMax')
                ->setParameter('milkMax', (float) $filters['milk_max']);
        }

        if (isset($filters['weight_min']) && $filters['weight_min'] !== '') {
            $qb->andWhere('c.weight >= :weightMin')
                ->setParameter('weightMin', (float) $filters['weight_min']);
        }

        if (isset($filters['weight_max']) && $filters['weight_max'] !== '') {
            $qb->andWhere('c.weight <= :weightMax')
                ->setParameter('weightMax', (float) $filters['weight_max']);
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

    /**
     * @return Cow[]
     */
    public function findTop10MilkProducers(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->addSelect('f')
            ->where('c.slaughter IS NULL')
            ->orderBy('c.milk', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Cow[]
     */
    public function findTop10FeedConsumersOverOneYear(): array
    {
        $oneYearAgo = new \DateTime('-1 year');

        return $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->addSelect('f')
            ->where('c.slaughter IS NULL')
            ->andWhere('c.birthdate <= :oneYearAgo')
            ->setParameter('oneYearAgo', $oneYearAgo)
            ->orderBy('c.feed', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<array{farmName: string, totalFeed: float}>
     */
    public function getTotalFeedPerFarm(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('f.name AS farmName, COALESCE(SUM(c.feed), 0) AS totalFeed')
            ->from(\App\Entity\Farm::class, 'f')
            ->leftJoin(\App\Entity\Cow::class, 'c', 'WITH', 'c.farm = f AND c.slaughter IS NULL')
            ->groupBy('f.id')
            ->orderBy('totalFeed', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findMilkReport(?int $farmId, ?float $milkMin, ?float $milkMax): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->addSelect('f')
            ->where('c.slaughter IS NULL')
            ->orderBy('c.milk', 'DESC');

        if ($farmId) {
            $qb->andWhere('f.id = :farmId')
                ->setParameter('farmId', $farmId);
        }

        if ($milkMin !== null) {
            $qb->andWhere('c.milk >= :milkMin')
                ->setParameter('milkMin', $milkMin);
        }

        if ($milkMax !== null) {
            $qb->andWhere('c.milk <= :milkMax')
                ->setParameter('milkMax', $milkMax);
        }

        return $qb;
    }

    public function findFeedReport(?int $farmId, ?float $feedMin, ?float $feedMax): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->addSelect('f')
            ->where('c.slaughter IS NULL')
            ->orderBy('c.feed', 'DESC');

        if ($farmId) {
            $qb->andWhere('f.id = :farmId')
                ->setParameter('farmId', $farmId);
        }

        if ($feedMin !== null) {
            $qb->andWhere('c.feed >= :feedMin')
                ->setParameter('feedMin', $feedMin);
        }

        if ($feedMax !== null) {
            $qb->andWhere('c.feed <= :feedMax')
                ->setParameter('feedMax', $feedMax);
        }

        return $qb;
    }

    public function findYoungHighFeedReport(?int $farmId, ?float $feedMin): QueryBuilder
    {
        $oneYearAgo = new \DateTime('-1 year');

        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->addSelect('f')
            ->where('c.slaughter IS NULL')
            ->andWhere('c.birthdate >= :oneYearAgo')
            ->andWhere('c.feed > 500')
            ->setParameter('oneYearAgo', $oneYearAgo)
            ->orderBy('c.feed', 'DESC');

        if ($farmId) {
            $qb->andWhere('f.id = :farmId')
                ->setParameter('farmId', $farmId);
        }

        if ($feedMin !== null) {
            $qb->andWhere('c.feed >= :feedMin')
                ->setParameter('feedMin', $feedMin);
        }

        return $qb;
    }

    public function findSlaughtered(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.farm', 'f')
            ->addSelect('f')
            ->where('c.slaughter IS NOT NULL')
            ->orderBy('c.slaughter', 'DESC');
    }
}
