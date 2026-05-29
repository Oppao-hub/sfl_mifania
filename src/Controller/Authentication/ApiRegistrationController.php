<?php

namespace App\Controller\Authentication;

use App\Entity\Cart;
use App\Entity\Customer;
use App\Entity\User;
use App\Entity\Wallet;
use App\Service\EmailVerificationService;
use App\Service\PasswordPolicy;
use App\Service\RegisterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiRegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationService $emailVerificationService,
        private ValidatorInterface $validator,
        private RegisterNotifier $registerNotifier
    ) {}

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, PasswordPolicy $passwordPolicy): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid request body',
            ], 400);
        }

        // Validate required fields
        if (!isset($data['firstName']) || !isset($data['lastName']) || !isset($data['email']) || !isset($data['password'])) {
            return $this->json([
                'success' => false,
                'message' => 'First Name, Last Name, email, and password are required',
            ], 400);
        }

        $email = strtolower(trim((string) $data['email']));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid email address',
            ], 400);
        }

        $passwordError = $passwordPolicy->validate((string) $data['password']);
        if ($passwordError !== null) {
            return $this->json([
                'success' => false,
                'message' => $passwordError,
            ], 400);
        }

        // Check if email already exists (case-insensitive)
        $existingEmail = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]) ?? $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('LOWER(u.email) = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingEmail) {
            return $this->json([
                'success' => false,
                'message' => 'Email already registered',
            ], 409);
        }

        // Create new user
        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_CUSTOMER']);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, (string) $data['password']);
        $user->setPassword($hashedPassword);

        // Generate verification token
        $verificationToken = $this->emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($verificationToken);
        $user->setIsVerified(false);

        // Validate entity
        $errors = $this->validator->validate($user);
        if (\count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], 400);
        }

        //Create new customer
        $customer = new Customer();
        $customer->setUser($user);

        //Create new wallet
        $wallet = new Wallet();
        $wallet->setBalance(0.00);
        $wallet->setRewardPoints(0);
        $wallet->setCustomer($customer);


        //Create new cart
        $cart = new Cart();
        $cart->setCustomer($customer);

        //Set value to customer
        $customer->setFirstName($data['firstName']);
        $customer->setLastName($data['lastName']);

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->persist($customer);
        $this->entityManager->persist($wallet);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        // Generate verification URL
        $verificationUrl = $this->generateUrl(
            'app_email_verification',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send verification email
        try {
            $this->registerNotifier->sendNewUserNotification($user);//for admin
            $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);//for user
        } catch (\Exception $e) {
            // Log error but don't fail registration
            // User can request resend later
        }

        return $this->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
            'user' => [
                'id' => $user->getId(),
                'firstName' => $customer->getFirstName(),
                'lastName' => $customer->getLastName(),
                'email' => $user->getEmail(),
                'isVerified' => $user->getIsVerified(),
                'roles' => $user->getRoles()
            ]
        ], 201);
    }
}
