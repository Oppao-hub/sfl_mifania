<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Shared password-reset request flow for web and mobile API.
 */
class PasswordResetRequestService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private ResetPasswordRequestRepository $resetPasswordRequestRepository,
        private PasswordResetMailerService $passwordResetMailer,
        private LoggerInterface $logger,
    ) {
    }

    public function sendPasswordResetEmailForAddress(string $email): ?ResetPasswordToken
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            return null;
        }

        $user = $this->findUserByEmail($normalizedEmail);

        if (!$user) {
            return null;
        }

        // Allow resend from another channel (e.g. web then mobile) by replacing any pending request.
        $this->resetPasswordRequestRepository->removeRequests($user);

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->logger->warning('Password reset token could not be generated.', [
                'email' => $normalizedEmail,
                'reason' => $e->getReason(),
            ]);

            return null;
        }

        try {
            $this->passwordResetMailer->sendPasswordResetEmail($user, $resetToken);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send password reset email.', [
                'email' => $normalizedEmail,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return $resetToken;
    }

    private function findUserByEmail(string $normalizedEmail): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $normalizedEmail,
        ]);

        if ($user instanceof User) {
            return $user;
        }

        // Accounts created via mobile may have mixed-case emails stored in the database.
        return $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('LOWER(u.email) = :email')
            ->setParameter('email', $normalizedEmail)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
