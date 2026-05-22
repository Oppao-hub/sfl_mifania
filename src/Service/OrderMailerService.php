<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderMailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $router,
        private string $adminEmail
    ) {}

    public function sendStatusUpdateEmail(Order $order): void
    {
        $customer = $order->getCustomer();
        if (!$customer || !$customer->getUser()) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from('Mifania Sustainable Fashion Line <' . $this->adminEmail . '>')
            ->to($customer->getUser()->getEmail())
            ->subject('Update regarding your Order #' . $order->getId())
            ->htmlTemplate('email/order_status_update.html.twig')
            ->context([
                'order' => $order,
                'customer' => $customer,
                'status' => $order->getOrderStatus()->value,
                'order_url' => $this->router->generate('app_account_order_view', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        $this->mailer->send($email);
    }
}
