<?php

namespace App\Controller\Frontend;

use App\Entity\User;
use App\Form\CustomerProfileType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

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
        // SECURITY: Ensure the logged-in user actually owns this order!
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
        ]);
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
                $newFileName = $slugger->slug(pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME)).'-'.uniqid().'.'.$imageFile->guessExtension();
                $imageFile->move($this->getParameter('customer_images_directory'), $newFileName);
                $customer->setAvatar($newFileName);
            }
            $em->flush();
            $this->addFlash('success', 'Customer profile updated.');
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
