<?php

namespace App\Controller;

use App\Entity\Cow;
use App\Entity\Farm;
use App\Entity\Veterinarian;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dev')]
class DevController extends AbstractController
{
    #[Route('', name: 'app_dev_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException();
        }

        return $this->render('dev/index.html.twig', [
            'veterinarianCount' => $em->getRepository(Veterinarian::class)->count([]),
            'farmCount' => $em->getRepository(Farm::class)->count([]),
            'cowCount' => $em->getRepository(Cow::class)->count([]),
        ]);
    }

    #[Route('/reset', name: 'app_dev_reset', methods: ['POST'])]
    public function reset(Request $request, EntityManagerInterface $em): Response
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('reset', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');

            return $this->redirectToRoute('app_dev_index');
        }

        $connection = $em->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE cow');
        $connection->executeStatement('TRUNCATE TABLE farm_veterinarian');
        $connection->executeStatement('TRUNCATE TABLE farm');
        $connection->executeStatement('TRUNCATE TABLE veterinarian');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $this->addFlash('success', 'Banco de dados resetado com sucesso.');

        return $this->redirectToRoute('app_dev_index');
    }

    #[Route('/seed', name: 'app_dev_seed', methods: ['POST'])]
    public function seed(Request $request, EntityManagerInterface $em): Response
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('seed', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF inválido.');

            return $this->redirectToRoute('app_dev_index');
        }

        $vetNames = [
            'Dr. Carlos Mendes', 'Dra. Ana Beatriz', 'Dr. Roberto Lima',
            'Dra. Fernanda Costa', 'Dr. João Pereira', 'Dra. Mariana Souza',
            'Dr. Pedro Alves', 'Dra. Juliana Rocha', 'Dr. Lucas Ferreira',
            'Dra. Camila Ribeiro',
        ];

        $vetCrmvs = [
            'CRMV-SP 12345', 'CRMV-MG 23456', 'CRMV-GO 34567',
            'CRMV-MT 45678', 'CRMV-MS 56789', 'CRMV-PR 67890',
            'CRMV-BA 78901', 'CRMV-RS 89012', 'CRMV-TO 90123',
            'CRMV-PA 01234',
        ];

        $veterinarians = [];
        for ($i = 0; $i < 10; $i++) {
            $vet = new Veterinarian();
            $vet->setName($vetNames[$i]);
            $vet->setCrmv($vetCrmvs[$i]);
            $em->persist($vet);
            $veterinarians[] = $vet;
        }

        $farmData = [
            ['Fazenda Boa Esperança', 45.0, 'José Antônio Silva'],
            ['Fazenda Santa Maria', 78.5, 'Maria Helena Santos'],
            ['Fazenda São Jorge', 32.0, 'Jorge Luiz Oliveira'],
            ['Fazenda Primavera', 120.0, 'Ana Paula Ferreira'],
            ['Fazenda Serra Bonita', 55.0, 'Carlos Eduardo Lima'],
        ];

        $farms = [];
        foreach ($farmData as $i => [$name, $size, $manager]) {
            $farm = new Farm();
            $farm->setName($name);
            $farm->setSize($size);
            $farm->setManager($manager);

            $assignedVets = array_rand($veterinarians, rand(1, 3));
            if (!is_array($assignedVets)) {
                $assignedVets = [$assignedVets];
            }
            foreach ($assignedVets as $idx) {
                $farm->addVeterinarian($veterinarians[$idx]);
            }

            $em->persist($farm);
            $farms[] = $farm;
        }

        $breeds = ['NL', 'GR', 'HO', 'JE', 'AN', 'BR', 'SI', 'GU'];
        for ($i = 1; $i <= 100; $i++) {
            $cow = new Cow();
            $breed = $breeds[array_rand($breeds)];
            $cow->setCode(sprintf('%s-%03d', $breed, $i));
            $cow->setMilk(rand(10, 200) / 1.0);
            $cow->setFeed(rand(50, 500) / 1.0);
            $cow->setWeight(rand(150, 600) / 1.0);
            $cow->setBirthdate(new \DateTime('-' . rand(1, 8) . ' years -' . rand(0, 11) . ' months'));
            $cow->setFarm($farms[array_rand($farms)]);
            $em->persist($cow);
        }

        $em->flush();

        $this->addFlash('success', 'Seeds criadas: 10 veterinários, 5 fazendas e 100 gados.');

        return $this->redirectToRoute('app_dev_index');
    }
}
