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
        if (empty($this->adminEmail)) {
            // You can use error_log to see this in your terminal/logs
            error_log("CRITICAL: Admin email is empty. Check .env.local");
            return;
        }

        $email = (new TemplatedEmail())
            ->from('Mifania System <mifaniapaolo0012@gmail.com>')
            ->to($this->adminEmail)
            ->subject('🚀 New User Registration: ' . $user->getEmail())
            ->htmlTemplate('email/index.html.twig')
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
