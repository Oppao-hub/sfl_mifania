<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use App\Service\NotificationPublisher;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JwtAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private ActivityLogger $logger,
        private NotificationPublisher $notificationPublisher,
        private UserRepository $userRepository
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        /** @var User $user */
        $user = $token->getUser();

        // Check if email is verified
        if (!$user->getIsVerified()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Please verify your email address before logging in',
                'verified' => false
            ], 403);
        }

        // --- CUSTOM NOTIFICATIONS START ---

        // 1. Notify the User themselves (Security Alert)
        $this->notificationPublisher->send(
            $user,
            'Security Alert: Mobile Login',
            "A new login was detected on your account via Mobile App.",
            'app_account',
            [],
            'security',
            false
        );

        // 2. Notify Management (Admins & Staff)
        $management = $this->userRepository->findAllManagement();
        foreach ($management as $manager) {
            if ($manager->getId() === $user->getId()) {
                continue;
            }

            $this->notificationPublisher->send(
                $manager,
                'Mobile Login Detected',
                "User {$user->getEmail()} has logged in via Mobile App.",
                'app_dashboard',
                [],
                'system',
                false
            );
        }

        // 3. Log the Activity AND Flush
        $this->logger->log(
            'LOGIN',
            "User {$user->getUserIdentifier()} logged in via Mobile App API.",
            $user,
            true
        );

        // --- CUSTOM NOTIFICATIONS END ---

        // Generate JWT token
        $jwt = $this->jwtManager->create($user);

        $userData = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'verified' => $user->getIsVerified()
        ];

        if ($customer = $user->getCustomer()) {
            $userData['customerId'] = $customer->getId();
            $userData['customer'] = '/api/customers/' . $customer->getId();
            $userData['firstName'] = $customer->getFirstName();
            $userData['lastName'] = $customer->getLastName();
        }

        return new JsonResponse([
            'token' => $jwt,
            'user' => $userData
        ]);
    }
}
