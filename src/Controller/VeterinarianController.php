<?php

namespace App\Controller;

use App\Entity\Veterinarian;
use App\Form\VeterinarianType;
use App\Repository\VeterinarianRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/veterinarians', name: 'app_veterinarian_')]
class VeterinarianController extends AbstractController
{
    public function __construct(
        private VeterinarianRepository $repository,
    ) {
    }

    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $query = $this->repository->findBySearch($search);

        $veterinarians = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('veterinarian/index.html.twig', [
            'veterinarians' => $veterinarians,
            'search' => $search,
        ]);
    }

    #[Route('/create', name: 'create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $veterinarian = new Veterinarian();
        $form = $this->createForm(VeterinarianType::class, $veterinarian);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($veterinarian, true);
            $this->addFlash('success', 'Veterinário cadastrado com sucesso.');

            return $this->redirectToRoute('app_veterinarian_index');
        }

        return $this->render('veterinarian/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Veterinarian $veterinarian): Response
    {
        return $this->render('veterinarian/show.html.twig', [
            'veterinarian' => $veterinarian,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Veterinarian $veterinarian): Response
    {
        $form = $this->createForm(VeterinarianType::class, $veterinarian);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->repository->save($veterinarian, true);
            $this->addFlash('success', 'Veterinário atualizado com sucesso.');

            return $this->redirectToRoute('app_veterinarian_index');
        }

        return $this->render('veterinarian/edit.html.twig', [
            'veterinarian' => $veterinarian,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Veterinarian $veterinarian): Response
    {
        if ($this->isCsrfTokenValid('delete' . $veterinarian->getId(), $request->request->get('_token'))) {
            $this->repository->remove($veterinarian, true);
            $this->addFlash('success', 'Veterinário removido com sucesso.');
        }

        return $this->redirectToRoute('app_veterinarian_index');
    }
}
