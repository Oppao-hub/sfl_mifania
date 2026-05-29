<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Generates secure temporary passwords for admin-initiated resets.
 * Password is shown once on the user edit screen (session), never in flash messages.
 */
final class AdminPasswordResetService
{
    private const SESSION_KEY = 'admin_one_time_password';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function resetToTemporaryPassword(
        User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): string {
        $plainPassword = $this->generateTemporaryPassword();
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
        $em->flush();

        $this->storeOneTimePasswordForDisplay($user, $plainPassword);

        return $plainPassword;
    }

    public function consumeOneTimePassword(Request $request, User $user): ?string
    {
        $session = $request->getSession();
        $data = $session->get(self::SESSION_KEY);

        if (!\is_array($data) || ($data['userId'] ?? null) !== $user->getId()) {
            return null;
        }

        $session->remove(self::SESSION_KEY);

        $password = $data['password'] ?? null;

        return \is_string($password) && $password !== '' ? $password : null;
    }

    private function storeOneTimePasswordForDisplay(User $user, string $plainPassword): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, [
            'userId' => $user->getId(),
            'password' => $plainPassword,
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghjkmnpqrstuvwxyz';
        $digits = '23456789';
        $symbols = '!@#$%&*';
        $all = $upper.$lower.$digits.$symbols;

        $password = $upper[random_int(0, \strlen($upper) - 1)]
            .$lower[random_int(0, \strlen($lower) - 1)]
            .$digits[random_int(0, \strlen($digits) - 1)]
            .$symbols[random_int(0, \strlen($symbols) - 1)];

        for ($i = 0; $i < 12; ++$i) {
            $password .= $all[random_int(0, \strlen($all) - 1)];
        }

        return str_shuffle($password);
    }
}
