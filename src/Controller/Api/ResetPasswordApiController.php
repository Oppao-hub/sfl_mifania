<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/api/reset-password')]
class ResetPasswordApiController extends AbstractController
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/request', name: 'api_reset_password_request', methods: ['POST'])]
    public function request(Request $request, MailerInterface $mailer): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $email = trim((string) ($payload['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('Please enter a valid email address.');
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        if ($user) {
            try {
                $resetToken = $this->resetPasswordHelper->generateResetToken($user);
            } catch (ResetPasswordExceptionInterface) {
                // Do not reveal whether a user account was found or why email was not sent.
                return $this->genericRequestResponse();
            }

            $emailMessage = (new TemplatedEmail())
                ->from(new Address('mifaniapaolo0012@gmail.com', 'Mifania Security'))
                ->to((string) $user->getEmail())
                ->subject('Your password reset request')
                ->htmlTemplate('reset_password/email_api.html.twig')
                ->context([
                    'resetToken' => $resetToken,
                ]);

            $mailer->send($emailMessage);
        }

        return $this->genericRequestResponse();
    }

    #[Route('/reset', name: 'api_reset_password_reset', methods: ['POST'])]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid request body.');
        }

        $token = trim((string) ($payload['token'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $confirmPassword = (string) ($payload['confirmPassword'] ?? $password);

        if ($token === '') {
            throw new BadRequestHttpException('Reset token is required.');
        }

        if ($password === '' || $confirmPassword === '') {
            throw new BadRequestHttpException('Password and confirmation are required.');
        }

        if ($password !== $confirmPassword) {
            throw new BadRequestHttpException('The password fields must match.');
        }

        if (\strlen($password) < 8) {
            throw new BadRequestHttpException('Password must be at least 8 characters long.');
        }

        if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_])/', $password)) {
            throw new BadRequestHttpException(
                'Password must contain an uppercase letter, a lowercase letter, a number, and a symbol.',
            );
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            throw new BadRequestHttpException(
                'This password reset link is invalid or has expired. Please request a new one.',
            );
        }

        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $this->resetPasswordHelper->removeResetRequest($token);
        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Your password has been reset. You can now sign in with your new password.',
        ]);
    }

    private function genericRequestResponse(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'message' => 'If an account exists for that email, you will receive a password reset link shortly.',
        ]);
    }
}
