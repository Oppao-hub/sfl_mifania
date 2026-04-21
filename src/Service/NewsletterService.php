<?php

namespace App\Service;

use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
// use Doctrine\ORM\EntityManagerInterface; // Uncomment if saving to DB

class NewsletterService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail,
    ) {
    }

    public function processNewSubscription(string $emailAddress): void
    {
        // 1. (Optional) Save to your database here

        // 2. Send the Welcome Email
        $this->sendWelcomeEmail($emailAddress);

        // 3. (Future) Add to Brevo Contact List via API here
    }

    private function sendWelcomeEmail(string $recipientEmail): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->adminEmail, 'Mifania Sustainable Fashion Line'))
            ->to($recipientEmail)
            ->subject('Welcome to the Collective (+ 10% Off Your First Order) 🌿')
            ->htmlTemplate('email/newsletter_welcome.html.twig')
            ->context([
                'recipient' => $recipientEmail,
                'discount_code' => 'EARTHFIRST10',
            ]);

        $this->mailer->send($email);
    }
}
