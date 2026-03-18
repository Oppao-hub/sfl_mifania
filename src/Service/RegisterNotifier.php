<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class RegisterNotifier
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail
    ) {}

    public function sendNewUserNotification(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from('Mifania System <mifaniapaolo0012@gmail.com>')
            ->to($this->adminEmail)
            ->subject('🚀 New User Registration: ' . $user->getEmail())
            ->htmlTemplate('email/admin_email.html.twig')
            ->context([
                'user' => $user,
                'date' => new \DateTime(),
            ]);

        $this->mailer->send($email);
    }

    public function sendUserWelcomeEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from('mifaniapaolo0012@gmail.com') // Your verified Gmail
            ->to($user->getEmail())             // The user who just registered!
            ->subject('Welcome to Mifania Sustainable Fashion Line!')
            ->htmlTemplate('email/welcome.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
