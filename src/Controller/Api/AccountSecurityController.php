<?php

namespace App\Controller\Api;

use App\Entity\Enum\AccountStatus;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/account')]
#[IsGranted('ROLE_CUSTOMER')]
class AccountSecurityController extends AbstractController
{
    #[Route('/change-password', name: 'api_account_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        #[CurrentUser] User $user,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $currentPassword = (string) ($payload['currentPassword'] ?? '');
        $newPassword = (string) ($payload['newPassword'] ?? '');
        $confirmPassword = (string) ($payload['confirmPassword'] ?? $newPassword);

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            throw new BadRequestHttpException('Current password, new password, and confirmation are required.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new BadRequestHttpException('The new password fields must match.');
        }

        if (\strlen($newPassword) < 8) {
            throw new BadRequestHttpException('Password must be at least 8 characters long.');
        }

        if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_])/', $newPassword)) {
            throw new BadRequestHttpException(
                'Password must contain an uppercase letter, a lowercase letter, a number, and a symbol.',
            );
        }

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            throw new BadRequestHttpException('Your current password is incorrect.');
        }

        if ($passwordHasher->isPasswordValid($user, $newPassword)) {
            throw new BadRequestHttpException('Your new password must be different from your current password.');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Your password has been changed.',
        ]);
    }

    #[Route('/deactivate', name: 'api_account_deactivate', methods: ['POST'])]
    public function deactivate(
        #[CurrentUser] User $user,
        EntityManagerInterface $em,
    ): JsonResponse {
        if ($user->getStatus() === AccountStatus::Deactivated) {
            throw new BadRequestHttpException('Your account is already deactivated.');
        }

        $user->setStatus(AccountStatus::Deactivated);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Your account has been deactivated. You can sign in again when you reactivate it with support.',
        ]);
    }
}
