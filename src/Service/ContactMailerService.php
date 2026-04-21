<?php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Service handled for sending contact form messages to administrators.
 */
class ContactMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $adminEmail
    ) {}

    /**
     * Sends a contact form message using the configured mailer transport (e.g., Brevo).
     */
    public function sendContactMessage(string $name, string $customerEmail, string $subject, string $message): void
    {
        $email = (new TemplatedEmail())
            ->from('Mifania Sustainable Fashion Line <mifaniapaolo0012@gmail.com>')
            ->replyTo($customerEmail)
            ->to($this->adminEmail)
            ->subject('Mifania Sustainable Fashion Line - New Contact Inquiry: ' . $subject)
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
