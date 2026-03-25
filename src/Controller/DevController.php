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

        $veterinarians = [];
        for ($i = 1; $i <= 10; $i++) {
            $vet = new Veterinarian();
            $vet->setName('Veterinário ' . uniqid());
            $vet->setCrmv('CRMV-' . strtoupper(uniqid()));
            $em->persist($vet);
            $veterinarians[] = $vet;
        }

        $farms = [];
        for ($i = 1; $i <= 5; $i++) {
            $farm = new Farm();
            $farm->setName('Fazenda ' . uniqid());
            $farm->setSize(rand(10, 100));
            $farm->setManager('Gerente ' . $i);

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

        for ($i = 1; $i <= 100; $i++) {
            $cow = new Cow();
            $cow->setCode('COW-' . strtoupper(uniqid()));
            $cow->setMilk(rand(10, 200) / 1.0);
            $cow->setFeed(rand(50, 500) / 1.0);
            $cow->setWeight(rand(150, 600) / 1.0);
            $cow->setBirthdate(new \DateTime('-' . rand(1, 8) . ' years -' . rand(0, 11) . ' months'));
            $cow->setFarm($farms[array_rand($farms)]);
            $em->persist($cow);
        }

        $em->flush();

        $this->addFlash('success', 'Seeds criadas: 10 veterinários, 5 fazendas e 100 vacas.');

        return $this->redirectToRoute('app_dev_index');
    }
}
