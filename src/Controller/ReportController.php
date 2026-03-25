<?php

namespace App\Controller;

use App\Repository\CowRepository;
use App\Repository\FarmRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/reports', name: 'app_report_')]
class ReportController extends AbstractController
{
    public function __construct(
        private CowRepository $cowRepository,
        private FarmRepository $farmRepository,
    ) {
    }

    #[Route('/milk', name: 'milk', methods: ['GET'])]
    public function milk(Request $request, PaginatorInterface $paginator): Response
    {
        $farmId = $request->query->get('farm') ? (int) $request->query->get('farm') : null;
        $milkMin = $this->parsePositiveFloat($request->query->get('milk_min'));
        $milkMax = $this->parsePositiveFloat($request->query->get('milk_max'));

        $query = $this->cowRepository->findMilkReport($farmId, $milkMin, $milkMax);

        $cows = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('report/milk.html.twig', [
            'cows' => $cows,
            'farms' => $this->farmRepository->findBy([], ['name' => 'ASC']),
            'totalMilk' => $this->cowRepository->getTotalMilkPerWeek(),
            'filters' => [
                'farm' => $farmId,
                'milk_min' => $milkMin,
                'milk_max' => $milkMax,
            ],
        ]);
    }

    #[Route('/feed', name: 'feed', methods: ['GET'])]
    public function feed(Request $request, PaginatorInterface $paginator): Response
    {
        $farmId = $request->query->get('farm') ? (int) $request->query->get('farm') : null;
        $feedMin = $this->parsePositiveFloat($request->query->get('feed_min'));
        $feedMax = $this->parsePositiveFloat($request->query->get('feed_max'));

        $query = $this->cowRepository->findFeedReport($farmId, $feedMin, $feedMax);

        $cows = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('report/feed.html.twig', [
            'cows' => $cows,
            'farms' => $this->farmRepository->findBy([], ['name' => 'ASC']),
            'totalFeed' => $this->cowRepository->getTotalFeedPerWeek(),
            'feedPerFarm' => $this->cowRepository->getTotalFeedPerFarm(),
            'filters' => [
                'farm' => $farmId,
                'feed_min' => $feedMin,
                'feed_max' => $feedMax,
            ],
        ]);
    }

    #[Route('/young-high-feed', name: 'young_high_feed', methods: ['GET'])]
    public function youngHighFeed(Request $request, PaginatorInterface $paginator): Response
    {
        $farmId = $request->query->get('farm') ? (int) $request->query->get('farm') : null;
        $feedMin = $this->parsePositiveFloat($request->query->get('feed_min'));

        $query = $this->cowRepository->findYoungHighFeedReport($farmId, $feedMin);

        $cows = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            15
        );

        return $this->render('report/young_high_feed.html.twig', [
            'cows' => $cows,
            'farms' => $this->farmRepository->findBy([], ['name' => 'ASC']),
            'youngHighFeedCount' => $this->cowRepository->countYoungHighFeedCows(),
            'filters' => [
                'farm' => $farmId,
                'feed_min' => $feedMin,
            ],
        ]);
    }

    #[Route('/milk/csv', name: 'milk_csv', methods: ['GET'])]
    public function milkCsv(Request $request): StreamedResponse
    {
        $farmId = $request->query->get('farm') ? (int) $request->query->get('farm') : null;
        $milkMin = $this->parsePositiveFloat($request->query->get('milk_min'));
        $milkMax = $this->parsePositiveFloat($request->query->get('milk_max'));

        $cows = $this->cowRepository->findMilkReport($farmId, $milkMin, $milkMax)
            ->setMaxResults(10000)
            ->getQuery()
            ->getResult();

        return $this->createCsvResponse('relatorio_leite.csv', function () use ($cows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Código', 'Fazenda', 'Nascimento', 'Peso (kg)', 'Leite (L/sem)'], ';');

            foreach ($cows as $cow) {
                fputcsv($handle, [
                    $cow->getCode(),
                    $cow->getFarm()?->getName() ?? '—',
                    $cow->getBirthdate()?->format('d/m/Y'),
                    number_format($cow->getWeight(), 2, ',', '.'),
                    number_format($cow->getMilk(), 2, ',', '.'),
                ], ';');
            }

            fclose($handle);
        });
    }

    #[Route('/feed/csv', name: 'feed_csv', methods: ['GET'])]
    public function feedCsv(Request $request): StreamedResponse
    {
        $farmId = $request->query->get('farm') ? (int) $request->query->get('farm') : null;
        $feedMin = $this->parsePositiveFloat($request->query->get('feed_min'));
        $feedMax = $this->parsePositiveFloat($request->query->get('feed_max'));

        $cows = $this->cowRepository->findFeedReport($farmId, $feedMin, $feedMax)
            ->setMaxResults(10000)
            ->getQuery()
            ->getResult();

        return $this->createCsvResponse('relatorio_racao.csv', function () use ($cows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Código', 'Fazenda', 'Idade (anos)', 'Ração (kg/sem)', 'Ração Diária (kg)'], ';');

            foreach ($cows as $cow) {
                fputcsv($handle, [
                    $cow->getCode(),
                    $cow->getFarm()?->getName() ?? '—',
                    number_format($cow->getAgeInYears(), 1, ',', '.'),
                    number_format($cow->getFeed(), 2, ',', '.'),
                    number_format($cow->getDailyFeed(), 2, ',', '.'),
                ], ';');
            }

            fclose($handle);
        });
    }

    #[Route('/young-high-feed/csv', name: 'young_high_feed_csv', methods: ['GET'])]
    public function youngHighFeedCsv(Request $request): StreamedResponse
    {
        $farmId = $request->query->get('farm') ? (int) $request->query->get('farm') : null;
        $feedMin = $this->parsePositiveFloat($request->query->get('feed_min'));

        $cows = $this->cowRepository->findYoungHighFeedReport($farmId, $feedMin)
            ->setMaxResults(10000)
            ->getQuery()
            ->getResult();

        return $this->createCsvResponse('relatorio_jovens_alta_racao.csv', function () use ($cows) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Código', 'Fazenda', 'Nascimento', 'Idade (anos)', 'Peso (kg)', 'Ração (kg/sem)', 'Ração Diária (kg)', 'Leite (L/sem)'], ';');

            foreach ($cows as $cow) {
                fputcsv($handle, [
                    $cow->getCode(),
                    $cow->getFarm()?->getName() ?? '—',
                    $cow->getBirthdate()?->format('d/m/Y'),
                    number_format($cow->getAgeInYears(), 1, ',', '.'),
                    number_format($cow->getWeight(), 2, ',', '.'),
                    number_format($cow->getFeed(), 2, ',', '.'),
                    number_format($cow->getDailyFeed(), 2, ',', '.'),
                    number_format($cow->getMilk(), 2, ',', '.'),
                ], ';');
            }

            fclose($handle);
        });
    }

    private function createCsvResponse(string $filename, callable $callback): StreamedResponse
    {
        $response = new StreamedResponse($callback);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    private function parsePositiveFloat(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $float = (float) $value;

        return $float > 0 ? $float : null;
    }
}
