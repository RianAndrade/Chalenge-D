<?php

namespace App\Tests;

use App\Entity\Cow;
use App\Entity\Farm;
use App\Entity\Veterinarian;
use Doctrine\ORM\EntityManagerInterface;

trait DatabaseTestTrait
{
    protected function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    protected function truncateAll(): void
    {
        $em = $this->getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE cow');
        $connection->executeStatement('TRUNCATE TABLE farm_veterinarian');
        $connection->executeStatement('TRUNCATE TABLE farm');
        $connection->executeStatement('TRUNCATE TABLE veterinarian');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        $em->clear();
    }

    protected function createFarm(string $name = 'Fazenda Teste', float $size = 10.0, string $manager = 'João'): Farm
    {
        $farm = new Farm();
        $farm->setName($name);
        $farm->setSize($size);
        $farm->setManager($manager);

        $em = $this->getEntityManager();
        $em->persist($farm);
        $em->flush();

        return $farm;
    }

    protected function createVeterinarian(string $name = 'Dr. Teste', string $crmv = 'CRMV-001'): Veterinarian
    {
        $vet = new Veterinarian();
        $vet->setName($name);
        $vet->setCrmv($crmv);

        $em = $this->getEntityManager();
        $em->persist($vet);
        $em->flush();

        return $vet;
    }

    protected function createCow(
        string $code,
        Farm $farm,
        float $milk = 100.0,
        float $feed = 50.0,
        float $weight = 200.0,
        string $birthdate = '-2 years',
        bool $slaughtered = false,
    ): Cow {
        $cow = new Cow();
        $cow->setCode($code);
        $cow->setFarm($farm);
        $cow->setMilk($milk);
        $cow->setFeed($feed);
        $cow->setWeight($weight);
        $cow->setBirthdate(new \DateTime($birthdate));

        if ($slaughtered) {
            $cow->setSlaughter(new \DateTime());
        }

        $em = $this->getEntityManager();
        $em->persist($cow);
        $em->flush();

        return $cow;
    }

    protected function getCsrfToken(string $tokenId): string
    {
        $container = static::getContainer();

        return $container->get('security.csrf.token_manager')
            ->getToken($tokenId)
            ->getValue();
    }
}
