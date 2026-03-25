<?php

namespace App\Controller;

use App\Entity\Cow;
use App\Form\CowType;
use App\Repository\CowRepository;
use App\Repository\FarmRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cows', name: 'app_cow_')]
class CowController extends AbstractController
{
    public function __construct(
        private CowRepository $repository,
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator, FarmRepository $farmRepository): Response
    {
        $filters = [
            'search' => $request->query->get('search'),
            'farm' => $request->query->get('farm'),
            'status' => $request->query->get('status'),
            'milk_min' => $request->query->get('milk_min'),
            'milk_max' => $request->query->get('milk_max'),
            'weight_min' => $request->query->get('weight_min'),
            'weight_max' => $request->query->get('weight_max'),
        ];

        $query = $this->repository->findByFilters($filters);

        $cows = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('cow/index.html.twig', [
            'cows' => $cows,
            'filters' => $filters,
            'farms' => $farmRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $cow = new Cow();
        $form = $this->createForm(CowType::class, $cow);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($cow, true);
            $this->addFlash('success', 'Gado cadastrado com sucesso.');

            return $this->redirectToRoute('app_cow_index');
        }

        return $this->render('cow/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Cow $cow): Response
    {
        return $this->render('cow/show.html.twig', [
            'cow' => $cow,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Cow $cow): Response
    {
        $form = $this->createForm(CowType::class, $cow);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($cow, true);
            $this->addFlash('success', 'Gado atualizado com sucesso.');

            return $this->redirectToRoute('app_cow_index');
        }

        return $this->render('cow/edit.html.twig', [
            'cow' => $cow,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Cow $cow): Response
    {
        if ($this->isCsrfTokenValid('delete' . $cow->getId(), $request->request->get('_token'))) {
            $this->repository->remove($cow, true);
            $this->addFlash('success', 'Gado removido com sucesso.');
        }

        return $this->redirectToRoute('app_cow_index');
    }

    #[Route('/slaughter/report', name: 'slaughter_report', methods: ['GET'], priority: 1)]
    public function slaughterReport(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->repository->findSlaughtered();

        $cows = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('cow/slaughter_report.html.twig', [
            'cows' => $cows,
        ]);
    }

    #[Route('/slaughter/list', name: 'slaughter_list', methods: ['GET'], priority: 1)]
    public function slaughterList(Request $request, PaginatorInterface $paginator): Response
    {
        $query = $this->repository->findEligibleForSlaughter();

        $cows = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('cow/slaughter_list.html.twig', [
            'cows' => $cows,
        ]);
    }

    #[Route('/{id}/slaughter', name: 'slaughter', methods: ['POST'], priority: 1)]
    public function slaughter(Request $request, Cow $cow): Response
    {
        if (!$this->isCsrfTokenValid('slaughter' . $cow->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido, tente novamente.');

            return $this->redirectToRoute('app_cow_slaughter_list');
        }

        if (!$cow->isEligibleForSlaughter()) {
            $this->addFlash('error', 'Este animal não se enquadra nas condições para abate.');

            return $this->redirectToRoute('app_cow_slaughter_list');
        }

        $cow->setSlaughter(new \DateTime());
        $this->repository->save($cow, true);
        $this->addFlash('success', 'Animal enviado para abate com sucesso.');

        return $this->redirectToRoute('app_cow_slaughter_list');
    }

    #[Route('/{id}/revert-slaughter', name: 'revert_slaughter', methods: ['POST'], priority: 1)]
    public function revertSlaughter(Request $request, Cow $cow): Response
    {
        if (!$this->isCsrfTokenValid('revert_slaughter' . $cow->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido, tente novamente.');

            return $this->redirectToRoute('app_cow_slaughter_report');
        }

        if ($cow->isAlive()) {
            $this->addFlash('error', 'Este animal não está abatido.');

            return $this->redirectToRoute('app_cow_slaughter_report');
        }

        $cow->setSlaughter(null);
        $this->repository->save($cow, true);
        $this->addFlash('success', 'Abate revertido com sucesso. O animal voltou ao rebanho.');

        return $this->redirectToRoute('app_cow_slaughter_report');
    }
}
