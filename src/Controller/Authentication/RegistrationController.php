<?php

namespace App\Controller\Authentication;

use App\Entity\Admin;
use App\Entity\Cart;
use App\Entity\Customer;
use App\Entity\User;
use App\Entity\Wallet;
use App\Form\RegistrationFormType;
use App\Service\EmailVerificationService;
use App\Service\RegisterNotifier;
use App\Service\NotificationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        RegisterNotifier $registerNotifier,
        NotificationPublisher $notifier,
        EmailVerificationService $emailVerificationService,
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
            $user->setRoles(['ROLE_CUSTOMER']);
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // Generate verification token
            $verificationToken = $emailVerificationService->generateVerificationToken();
            $user->setVerificationToken($verificationToken);
            $user->setIsVerified(false);

            // Generate verification URL
            $verificationUrl = $this->generateUrl(
                'app_email_verification',
                ['token' => $verificationToken],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $customer = new Customer();
            $customer->setUser($user);
            $customer->setFirstName($form->get('firstName')->getData());
            $customer->setLastName($form->get('lastName')->getData());

            $wallet = new Wallet();
            $wallet->setBalance(0.00);
            $wallet->setRewardPoints(0);
            $wallet->setCustomer($customer);

            $cart = new Cart();
            $cart->setCustomer($customer);

            $entityManager->persist($user);
            $entityManager->persist($customer);
            $entityManager->persist($wallet);
            $entityManager->persist($cart);
            $entityManager->flush();

            // Send verification email (Wrapped in try-catch to prevent 500 errors if mailer is down)
            try {
                $registerNotifier->sendNewUserNotification($user);
                $emailVerificationService->sendVerificationEmail($user, $verificationUrl);
            } catch (\Exception $e) {
                // Silently fail mailer issues so the user is still created successfully
            }

            // 1. Fetch the main Admin from the database (Gets the first admin account)
        $adminUser = $entityManager->getRepository(Admin::class)->findOneBy([]);

        // 2. If an admin exists, send them the live notification
        if ($adminUser !== null && $adminUser->getUser()) {
            $notifier->send(
                $adminUser->getUser(),
                'New User Joined!',
                // Note: We changed $newUser to $user here because your variable is just called $user
                "User {$user->getEmail()} has just created an account.",
                'app_customer_index', // Make sure this route actually exists in your app!
                []
            );
        }

            $this->addFlash('success', 'Registration successful! Please check your email to verify your account.');

            // Redirect to Login instead of auto-logging in
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
