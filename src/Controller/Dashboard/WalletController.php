<?php

namespace App\Controller\Dashboard;

use App\Entity\Wallet;
use App\Form\WalletType;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wallet')]
final class WalletController extends AbstractController
{
    #[Route(name: 'app_dashboard_wallet_index', methods: ['GET'])]
    public function index(WalletRepository $walletRepository): Response
    {
        return $this->render('dashboard/wallet/index.html.twig', [
            'wallets' => $walletRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_dashboard_wallet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $wallet = new Wallet();
        $form = $this->createForm(WalletType::class, $wallet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($wallet);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_wallet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/wallet/new.html.twig', [
            'wallet' => $wallet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_wallet_show', methods: ['GET'])]
    public function show(Wallet $wallet): Response
    {
        return $this->render('dashboard/wallet/show.html.twig', [
            'wallet' => $wallet,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_dashboard_wallet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Wallet $wallet, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(WalletType::class, $wallet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard_wallet_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('dashboard/wallet/edit.html.twig', [
            'wallet' => $wallet,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_dashboard_wallet_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Wallet $wallet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $wallet->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($wallet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_dashboard_wallet_index', [], Response::HTTP_SEE_OTHER);
    }
}
