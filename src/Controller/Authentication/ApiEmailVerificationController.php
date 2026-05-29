<?php

namespace App\Controller\Authentication;

use App\Service\EmailVerificationResendService;
use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

#[Route('/api')]
class ApiEmailVerificationController extends AbstractController
{
    public function __construct(
        private EmailVerificationService $emailVerificationService,
    ) {
    }

    /**
     * Verify email with token
     */
    #[Route('/verify-email', name: 'api_verify_email', methods: ['POST', 'GET'])]
    public function verifyEmail(Request $request): JsonResponse
    {
        $token = $request->query->get('token');

        if(!$token && $request->getContent()){
            $data = json_decode($request->getContent(), true);
            $token = $data['token'] ?? null;
        }

        if (!$token) {
            return $this->json([
                'success' => false,
                'message' => 'Verification token is required'
            ], 400);
        }

        $user = $this->emailVerificationService->verifyToken($token);

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid or expired verification token'
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->getIsVerified()
            ]
        ], 200);
    }

    /**
     * Resend verification email by address (public — for users who cannot log in yet).
     */
    #[Route('/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(
        Request $request,
        EmailVerificationResendService $emailVerificationResendService,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);
        $email = '';

        if (\is_array($payload)) {
            $email = (string) ($payload['email'] ?? '');
        }

        $emailVerificationResendService->resendForEmail($email);

        return $this->json([
            'success' => true,
            'message' => 'If an account matching your email exists and is not yet verified, we sent a new verification link.',
        ]);
    }

    /**
     * Check verification status
     */
    #[Route('/verification-status', name: 'api_verification_status', methods: ['GET'])]
    public function verificationStatus(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        return $this->json([
            'success' => true,
            'isVerified' => $user->getIsVerified(),
            'email' => $user->getEmail()
        ], 200);
    }
}
