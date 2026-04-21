<?php

namespace App\Controller\Authentication;

use App\Entity\User;
use App\Entity\Customer;
use App\Entity\Wallet;
use App\Entity\Cart;
use App\Entity\Enum\AccountStatus;
use App\Service\RegisterNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiGoogleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private JWTTokenManagerInterface $jwtManager,
        private UserPasswordHasherInterface $passwordHasher,
        private RegisterNotifier $registerNotifier
    ) {}

    #[Route('/api/login/google', name: 'api_google_login', methods: ['POST'])]
    public function googleLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $idToken = $data['idToken'] ?? null;

        if (!$idToken) {
            return new JsonResponse(['error' => 'idToken is required'], 400);
        }

        try {
            // 1. Verify ID Token with Google
            $response = $this->httpClient->request('GET', 'https://oauth2.googleapis.com/tokeninfo', [
                'query' => ['id_token' => $idToken]
            ]);

            if ($response->getStatusCode() !== 200) {
                return new JsonResponse(['error' => 'Invalid ID Token'], 400);
            }

            $userData = $response->toArray();
            $email = $userData['email'] ?? null;

            if (!$email) {
                return new JsonResponse(['error' => 'Email not provided by Google'], 400);
            }

            // 2. Find or Create User
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                // Create new User
                $user = new User();
                $user->setEmail($email);
                $user->setRoles(['ROLE_CUSTOMER']);
                $user->setIsVerified(true);
                $user->setStatus(AccountStatus::Active);
                
                // Set a random password for security compliance
                $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));

                // Create Customer profile
                $customer = new Customer();
                $customer->setUser($user);
                $customer->setFirstName($userData['given_name'] ?? 'Google');
                $customer->setLastName($userData['family_name'] ?? 'User');

                // Initialize Wallet
                $wallet = new Wallet();
                $wallet->setBalance(0.00);
                $wallet->setRewardPoints(0);
                $wallet->setCustomer($customer);

                // Initialize Cart
                $cart = new Cart();
                $cart->setCustomer($customer);

                $this->entityManager->persist($user);
                $this->entityManager->persist($customer);
                $this->entityManager->persist($wallet);
                $this->entityManager->persist($cart);
                
                $this->entityManager->flush();

                // Notify admin of new user
                try {
                    $this->registerNotifier->sendNewUserNotification($user);
                    $this->registerNotifier->sendUserWelcomeEmail($user);
                } catch (\Exception $e) {
                    // Log error but don't fail authentication
                }
            } else {
                // If user exists, ensure they are verified (since Google verified them)
                if (!$user->getIsVerified()) {
                    $user->setIsVerified(true);
                    $this->entityManager->flush();
                }
            }

            // 3. Check if account is active
            if ($user->getStatus() !== AccountStatus::Active) {
                return new JsonResponse(['error' => 'Account is ' . strtolower($user->getStatus()->value)], 403);
            }

            // 4. Generate JWT
            $token = $this->jwtManager->create($user);

            return new JsonResponse([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'roles' => $user->getRoles(),
                    'verified' => $user->getIsVerified()
                ]
            ], 200);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Authentication failed: ' . $e->getMessage()], 500);
        }
    }
}
