<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class ContactMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail // Reusing the same admin email variable!
    ) {}

    public function sendContactMessage(string $name, string $customerEmail, string $subject, string $message): void
    {
        $email = (new TemplatedEmail())
            // 1. MUST send FROM your verified Gmail to avoid spam filters
            ->from('Mifania Website <mifaniapaolo0012@gmail.com>')

            // 2. But we set REPLY-TO to the customer, so when you hit "Reply" in your inbox, it goes to them!
            ->replyTo($customerEmail)

            // 3. Send it to your admin inbox
            ->to($this->adminEmail)
            ->subject('Mifania Contact Form: ' . $subject)
            ->htmlTemplate('email/contact_message.html.twig')
            ->context([
                'name' => $name,
                'sender_email' => $customerEmail,
                'subject' => $subject,
                'message_content' => $message,
                'date' => new \DateTime(),
            ]);

        $this->mailer->send($email);
    }
}
