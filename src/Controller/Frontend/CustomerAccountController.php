<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Form\CustomerProfileType;
use App\Form\ChangePasswordType;
use App\Form\WalletTopUpType;
use App\Form\WalletTransferType;
use App\Service\WalletManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException; // <-- Added FileException
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted; // <-- Added Security import
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

// 1. RBAC FIX: Ensure only logged-in Customers can access the account area
#[IsGranted('ROLE_CUSTOMER')]
#[Route('/account')]
class CustomerAccountController extends AbstractController
{
    #[Route('', name: 'app_account')]
    public function index(#[CurrentUser] User $user): Response
    {
        return $this->render('frontend/account/profile_info.html.twig', [
            'customer' => $user->getCustomer(),
        ]);
    }

    #[Route('/orders', name: 'app_account_orders')]
    public function orders(#[CurrentUser] User $user): Response
    {
        return $this->render('frontend/account/order.html.twig', [
            'orders' => $user->getCustomer()->getOrders(),
        ]);
    }

    #[Route('/orders/{id}', name: 'app_account_order_view', methods: ['GET'])]
    public function viewOrder(\App\Entity\Order $order, #[CurrentUser] User $user): Response
    {
        // SECURITY: Ensure the logged-in user actually owns this order! (Great job having this already!)
        if ($order->getCustomer() !== $user->getCustomer()) {
            throw $this->createAccessDeniedException('You do not have permission to view this order.');
        }

        return $this->render('frontend/account/order_view.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route(path: '/wallet', name: 'app_account_wallet', methods: ['GET'])]
    public function wallet(#[CurrentUser] User $user): Response
    {
        $customer = $user->getCustomer();
        $wallet = $customer ? $customer->getWallet() : null;

        return $this->render('frontend/account/wallet.html.twig', [
            'wallet' => $wallet,
            'topUpPresets' => [100, 500, 1000],
            'topUpForm' => $this->createForm(WalletTopUpType::class)->createView(),
            'transferForm' => $this->createForm(WalletTransferType::class)->createView(),
        ]);
    }

    #[Route(path: '/wallet/top-up', name: 'app_account_wallet_top_up', methods: ['POST'])]
    public function walletTopUp(Request $request, #[CurrentUser] User $user, WalletManager $walletManager): Response
    {
        $wallet = $user->getCustomer()?->getWallet();
        if (!$wallet) {
            $this->addFlash('error', 'Wallet not found.');
            return $this->redirectToRoute('app_account_wallet');
        }

        $form = $this->createForm(WalletTopUpType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $amount = (float) $form->get('amount')->getData();
            $description = $form->get('description')->getData();

            try {
                $walletManager->topUp($wallet, $amount, $description ?: null);
                $this->addFlash('success', sprintf('Successfully added ₱%s to your wallet.', number_format($amount, 2)));
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Please enter a valid top-up amount.');
        }

        return $this->redirectToRoute('app_account_wallet');
    }

    #[Route(path: '/wallet/transfer', name: 'app_account_wallet_transfer', methods: ['POST'])]
    public function walletTransfer(Request $request, #[CurrentUser] User $user, WalletManager $walletManager): Response
    {
        $wallet = $user->getCustomer()?->getWallet();
        if (!$wallet) {
            $this->addFlash('error', 'Wallet not found.');
            return $this->redirectToRoute('app_account_wallet');
        }

        $form = $this->createForm(WalletTransferType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipientEmail = (string) $form->get('recipientEmail')->getData();
            $amount = (float) $form->get('amount')->getData();
            $note = $form->get('note')->getData();

            try {
                $walletManager->transfer($wallet, $recipientEmail, $amount, $note ?: null);
                $this->addFlash('success', sprintf('Successfully transferred ₱%s.', number_format($amount, 2)));
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Please check the transfer details and try again.');
        }

        return $this->redirectToRoute('app_account_wallet');
    }

    #[Route('/edit', name: 'app_account_edit')]
    public function edit(Request $request, #[CurrentUser] User $user, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $customer = $user->getCustomer();
        $form = $this->createForm(CustomerProfileType::class, $customer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('avatar')->getData();

            if ($imageFile) {
                // 2. BUG FIX: Save old avatar filename
                $oldAvatar = $customer->getAvatar();

                $newFileName = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($this->getParameter('customer_images_directory'), $newFileName);

                    // 3. BUG FIX: Delete old orphaned image from the customer uploads folder
                    if ($oldAvatar && $oldAvatar !== 'No Avatar Yet') {
                        $oldAvatarPath = $this->getParameter('customer_images_directory') . '/' . $oldAvatar;
                        if (file_exists($oldAvatarPath)) {
                            unlink($oldAvatarPath);
                        }
                    }

                    $customer->setAvatar($newFileName);
                } catch (FileException $e) {
                    // 4. BUG FIX: Prevent crash on upload failure
                    $this->addFlash('error', 'There was an error uploading your profile picture.');
                    return $this->redirectToRoute('app_account_edit');
                }
            }

            $em->flush();
            $this->addFlash('success', 'Your profile has been updated.');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('frontend/account/edit_profile.html.twig', ['form' => $form]);
    }

    #[Route('/password', name: 'app_account_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPlain = $form->get('plainPassword')->getData();
            $hashed = $passwordHasher->hashPassword($user, $newPlain);
            $user->setPassword($hashed);
            $em->flush();

            $this->addFlash('success', 'Your password has been changed.');
            return $this->redirectToRoute('app_account_password');
        }

        $status = ($form->isSubmitted() && !$form->isValid()) ? 422 : 200;

        return $this->render('frontend/account/password.html.twig', [
            'form' => $form->createView(),
        ], new Response(null, $status));
    }
}
