<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Shared email-verification resend flow for web and API.
 */
class EmailVerificationResendService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmailVerificationService $emailVerificationService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Sends a new verification email when the account exists and is not yet verified.
     * Returns true when an email was sent; false when no action was taken (unknown email or already verified).
     */
    public function resendForEmail(string $email): bool
    {
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            return false;
        }

        $user = $this->findUserByEmail($normalizedEmail);

        if (!$user instanceof User || $user->getIsVerified()) {
            return false;
        }

        $verificationToken = $this->emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($verificationToken);
        $this->entityManager->flush();

        $verificationUrl = $this->urlGenerator->generate(
            'app_email_verification',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);

        return true;
    }

    private function findUserByEmail(string $normalizedEmail): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $normalizedEmail,
        ]);

        if ($user instanceof User) {
            return $user;
        }

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
