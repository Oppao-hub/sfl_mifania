<?php

namespace App\Controller\Authentication;

use App\Entity\User;
use App\Service\PasswordResetMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
        private PasswordResetMailerService $passwordResetMailer,
        private LoggerInterface $logger,
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

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        if ($user) {
            try {
                $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            } catch (ResetPasswordExceptionInterface $e) {
                $this->logger->warning('Password reset token could not be generated.', [
                    'email' => $email,
                    'reason' => $e->getReason(),
                ]);

                return $this->successRequestResponse();
            }

            try {
                $this->passwordResetMailer->sendPasswordResetEmail($user, $resetToken);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send password reset email.', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->successRequestResponse();
    }

    #[Route('/reset', name: 'api_reset_password_reset', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
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

        $this->assertPasswordIsValid($newPassword, $confirmPassword);

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            throw new BadRequestHttpException(
                'This password reset link is invalid or has expired. Please request a new one.',
            );
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

    private function assertPasswordIsValid(string $newPassword, string $confirmPassword): void
    {
        if ($newPassword === '' || $confirmPassword === '') {
            throw new BadRequestHttpException('Password and confirmation are required.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new BadRequestHttpException('The password fields must match.');
        }

        if (\strlen($newPassword) < 8) {
            throw new BadRequestHttpException('Password must be at least 8 characters long.');
        }

        if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_])/', $newPassword)) {
            throw new BadRequestHttpException(
                'Password must contain an uppercase letter, a lowercase letter, a number, and a symbol.',
            );
        }
    }
}
