<?php

namespace App\Controller\Dashboard;

use App\Entity\Stock;
use App\Form\StockType;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_STAFF')]
#[Route('/dashboard/stocks')]
final class StockController extends AbstractController
{
    #[Route(path: '', name:'app_stock_index', methods: ['GET'])]
    public function index(StockRepository $stockRepository): Response
    {
        $stocks = $stockRepository->findAll();

        // 3. UX FIX: Added empty state handling so the page doesn't look broken when empty
        if (empty($stocks)) {
            $this->addFlash('warning', 'No Stocks found. Please create one first.');
            return $this->redirectToRoute('app_stock_new', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/stock/index.html.twig', [
            'stocks' => $stocks,
        ]);
    }

    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setAddedBy($this->getUser());
            $entityManager->persist($stock);
            $entityManager->flush();

            $this->addFlash('success', 'Stock created successfully!');
            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/stock/new.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        return $this->render('dashboard/stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockType::class, $stock);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stock->setUpdatedBy($this->getUser());
            $entityManager->flush();

            $this->addFlash('success', 'Stock updated successfully!');
            return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/stock/edit.html.twig', [
            'stock' => $stock,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $stock->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($stock);
            $entityManager->flush();
        }

        $this->addFlash('success', 'Stock deleted successfully!');
        return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
