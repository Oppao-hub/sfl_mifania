<?php

namespace App\Controller\Authentication;

use App\Entity\User;
use App\Service\PasswordPolicy;
use App\Service\PasswordResetRequestService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/reset-password')]
class ApiResetPasswordController extends AbstractController
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private PasswordResetRequestService $passwordResetRequestService,
    ) {
    }

    #[Route('/request', name: 'api_reset_password_request', methods: ['POST'])]
    public function requestReset(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Please enter a valid email address.');
        }

        $this->passwordResetRequestService->sendPasswordResetEmailForAddress($email);

        return $this->successRequestResponse();
    }

    #[Route('/reset', name: 'api_reset_password_reset', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        PasswordPolicy $passwordPolicy,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $token = trim((string) ($payload['token'] ?? ''));
        $newPassword = (string) ($payload['password'] ?? $payload['newPassword'] ?? '');
        $confirmPassword = (string) ($payload['confirmPassword'] ?? $newPassword);

        if ($token === '') {
            throw new BadRequestHttpException('Reset token is required.');
        }

        try {
            $passwordPolicy->assertMatching($newPassword, $confirmPassword);
            $passwordPolicy->assertValid($newPassword);
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            throw new BadRequestHttpException(
                'This password reset link is invalid or has expired. Please request a new one.',
            );
        }

        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            throw new BadRequestHttpException('Your new password must be different from your current password.');
        }

        $this->resetPasswordHelper->removeResetRequest($token);

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Your password has been reset. You can sign in with your new password.',
        ]);
    }

    private function successRequestResponse(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'If an account matching your email exists, we sent a password reset link to that address.',
        ]);
    }
}
