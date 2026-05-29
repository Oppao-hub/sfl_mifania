<?php

namespace App\Controller\Authentication;

use App\Entity\Cart;
use App\Entity\Customer;
use App\Entity\Enum\AccountStatus;
use App\Entity\User;
use App\Entity\Wallet;
use App\Service\GoogleIdTokenVerifier;
use App\Service\RegisterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ApiGoogleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GoogleIdTokenVerifier $googleIdTokenVerifier,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private RegisterNotifier $registerNotifier,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/api/login/google', name: 'api_google_login', methods: ['POST'])]
    public function googleLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!\is_array($data)) {
            return new JsonResponse(['message' => 'Invalid request body.'], Response::HTTP_BAD_REQUEST);
        }

        $idToken = $data['idToken'] ?? null;

        if (!\is_string($idToken) || $idToken === '') {
            return new JsonResponse(['message' => 'idToken is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $userData = $this->googleIdTokenVerifier->verify($idToken);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => 'Invalid Google ID token.'], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $e) {
            $this->logger->error('Google ID token verification failed.', ['error' => $e->getMessage()]);

            return new JsonResponse(['message' => 'Authentication failed. Please try again.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $email = strtolower(trim((string) ($userData['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'Email not provided by Google.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email])
            ?? $this->entityManager->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->where('LOWER(u.email) = :email')
                ->setParameter('email', $email)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

        $isNewUser = false;

        if (!$user instanceof User) {
            $isNewUser = true;
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_CUSTOMER']);
            $user->setIsVerified(true);
            $user->setStatus(AccountStatus::Active);
            $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));

            $customer = new Customer();
            $customer->setUser($user);
            $customer->setFirstName((string) ($userData['given_name'] ?? 'Google'));
            $customer->setLastName((string) ($userData['family_name'] ?? 'User'));

            $wallet = new Wallet();
            $wallet->setBalance(0.00);
            $wallet->setRewardPoints(0);
            $wallet->setCustomer($customer);

            $cart = new Cart();
            $cart->setCustomer($customer);

            $this->entityManager->persist($user);
            $this->entityManager->persist($customer);
            $this->entityManager->persist($wallet);
            $this->entityManager->persist($cart);
            $this->entityManager->flush();

            try {
                $this->registerNotifier->sendNewUserNotification($user);
                $this->registerNotifier->sendUserWelcomeEmail($user);
            } catch (\Throwable) {
                // Do not fail authentication when notification emails fail.
            }
        } else {
            if (!$user->getIsVerified()) {
                $user->setIsVerified(true);
                $this->entityManager->flush();
            }
        }

        if ($user->getStatus() === AccountStatus::Deactivated) {
            return new JsonResponse([
                'message' => 'Your account has been deactivated. Please contact an admin to reactivate your account.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($user->getStatus() !== AccountStatus::Active) {
            return new JsonResponse(['message' => 'Your account is not active. Please contact support.'], Response::HTTP_FORBIDDEN);
        }

        $token = $this->jwtManager->create($user);

        $userPayload = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'verified' => $user->getIsVerified(),
        ];

        if ($customer = $user->getCustomer()) {
            $userPayload['customerId'] = $customer->getId();
            $userPayload['customer'] = '/api/customers/'.$customer->getId();
            $userPayload['firstName'] = $customer->getFirstName();
            $userPayload['lastName'] = $customer->getLastName();
        }

        return new JsonResponse([
            'token' => $token,
            'user' => $userPayload,
            'is_new_user' => $isNewUser,
        ]);
    }
}
