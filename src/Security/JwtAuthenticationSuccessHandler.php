<?php

namespace App\Security;

use App\Entity\Enum\AccountStatus;
use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JwtAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
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

        if ($user->getStatus() === AccountStatus::Deactivated) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Your account has been deactivated. Please contact support to reactivate it.',
            ], 403);
        }

        // Login alerts are sent once via LoginLogoutSubscriber (LoginSuccessEvent).

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
