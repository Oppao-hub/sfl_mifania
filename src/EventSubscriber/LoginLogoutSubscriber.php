<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use App\Entity\User;

class LoginLogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InteractiveLoginEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            // Uses default flush=true
            $this->logger->log('LOGIN', "User {$user->getUserIdentifier()} logged in.", $user);
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        if ($event->getToken()) {
            $user = $event->getToken()->getUser();
            if ($user instanceof User) {
                // Uses default flush=true
                $this->logger->log('LOGOUT', "User {$user->getUserIdentifier()} logged out.", $user);
            }
        }
    }
}
