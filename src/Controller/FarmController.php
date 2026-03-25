<?php

namespace App\Controller;

use App\Entity\Farm;
use App\Form\FarmType;
use App\Repository\CowRepository;
use App\Repository\FarmRepository;
use App\Repository\VeterinarianRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/farms', name: 'app_farm_')]
class FarmController extends AbstractController
{
    public function __construct(
        private FarmRepository $repository,
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator, VeterinarianRepository $veterinarianRepository): Response
    {
        $filters = [
            'search' => $request->query->get('search'),
            'size_min' => $request->query->get('size_min'),
            'size_max' => $request->query->get('size_max'),
            'veterinarian' => $request->query->get('veterinarian'),
        ];

        $query = $this->repository->findByFilters($filters);

        $farms = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('farm/index.html.twig', [
            'farms' => $farms,
            'filters' => $filters,
            'veterinarians' => $veterinarianRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $farm = new Farm();
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($farm, true);
            $this->addFlash('success', 'Fazenda cadastrada com sucesso.');

            return $this->redirectToRoute('app_farm_index');
        }

        return $this->render('farm/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Farm $farm): Response
    {
        return $this->render('farm/show.html.twig', [
            'farm' => $farm,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Farm $farm, CowRepository $cowRepository): Response
    {
        $form = $this->createForm(FarmType::class, $farm);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $animals = $cowRepository->count(['farm' => $farm, 'slaughter' => null]);
            $limit = (int) floor($farm->getSize() * Farm::MAX_ANIMALS_PER_HECTARE);

            if ($animals > $limit) {
                $this->addFlash('error', "O tamanho não pode ser reduzido: a fazenda possui {$animals} animais, mas o novo tamanho comporta apenas {$limit}.");

                return $this->render('farm/edit.html.twig', [
                    'farm' => $farm,
                    'form' => $form,
                ]);
            }

            $this->repository->save($farm, true);
            $this->addFlash('success', 'Fazenda atualizada com sucesso.');

            return $this->redirectToRoute('app_farm_index');
        }

        return $this->render('farm/edit.html.twig', [
            'farm' => $farm,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Farm $farm, CowRepository $cowRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $farm->getId(), $request->request->get('_token'))) {
            if ($cowRepository->count(['farm' => $farm]) > 0) {
                $this->addFlash('error', 'Não é possível remover a fazenda pois ela possui animais cadastrados.');

                return $this->redirectToRoute('app_farm_index');
            }

            $this->repository->remove($farm, true);
            $this->addFlash('success', 'Fazenda removida com sucesso.');
        }

        return $this->redirectToRoute('app_farm_index');
    }
}
