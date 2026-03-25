<?php

namespace App\Controller;

use App\Repository\CowRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    public function __construct(
        private CowRepository $cowRepository,
    ) {
    }

    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'totalMilk' => $this->cowRepository->getTotalMilkPerWeek(),
            'totalFeed' => $this->cowRepository->getTotalFeedPerWeek(),
            'youngHighFeedCount' => $this->cowRepository->countYoungHighFeedCows(),
        ]);
    }
}
