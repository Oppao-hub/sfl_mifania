<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

/**
 * Sends password reset emails using the same mailer setup as verification / order emails.
 */
class PasswordResetMailerService
{
    public function __construct(
        private MessageBusInterface $messageBus,
        private UrlGeneratorInterface $urlGenerator,
        private string $adminEmail,
    ) {
    }

    public function sendPasswordResetEmail(User $user, ResetPasswordToken $resetToken): void
    {
        $token = $resetToken->getToken();

        $webResetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        $appResetUrl = sprintf('mifania://reset-password?token=%s', rawurlencode($token));

        $email = (new TemplatedEmail())
            ->from(new Address($this->adminEmail, 'Mifania Sustainable Fashion Line'))
            ->to(new Address((string) $user->getEmail()))
            ->subject('Mifania - Reset your password')
            ->htmlTemplate('email/password_reset.html.twig')
            ->context([
                'user' => $user,
                'resetToken' => $resetToken,
                'webResetUrl' => $webResetUrl,
                'appResetUrl' => $appResetUrl,
            ]);

        // Send synchronously so delivery does not depend on a messenger worker.
        $this->messageBus->dispatch(
            new SendEmailMessage($email),
            [new TransportNamesStamp('sync')],
        );
    }
}
