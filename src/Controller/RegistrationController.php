<?php

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\User;
use App\Entity\Wallet;
use App\Entity\Enum\Provider;
use App\Form\RegistrationFormType;
use App\Service\RegisterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        RegisterNotifier $registerNotifier
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // set user
            $user->setEmail($form->get('email')->getData());
            $user->setRoles(['ROLE_CUSTOMER']);
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setProvider(Provider::MANUAL);
            $user->setIsVerified(false);

            $customer = new Customer();
            $customer->setUser($user);
            $customer->setFirstName($form->get('firstName')->getData());
            $customer->setLastName($form->get('lastName')->getData());

            $wallet = new Wallet();
            $wallet->setBalance(0.00);
            $wallet->setRewardPoints(0);
            $wallet->setCustomer($customer);

            $entityManager->persist($user);
            $entityManager->persist($customer);
            $entityManager->persist($wallet);
            $entityManager->flush();

            $registerNotifier->sendNewUserNotification($user);
            $registerNotifier->sendUserWelcomeEmail($user);

            $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

            // Redirect to Login instead of auto-logging in
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
