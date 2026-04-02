<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
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
            LoginSuccessEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if ($user instanceof User) {
            $request = $event->getRequest();

            // Checking the raw URL path is safer than checking the route name during login
            $path = $request->getPathInfo();

            $loginMethod = 'Login Form';
            if (str_contains($path, 'google')) {
                $loginMethod = 'Google OAuth';
            }

            $this->logger->log(
                'LOGIN',
                "User {$user->getUserIdentifier()} logged in via {$loginMethod}.",
                $user
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        if ($event->getToken()) {
            $user = $event->getToken()->getUser();
            if ($user instanceof User) {
                $this->logger->log('LOGOUT', "User {$user->getUserIdentifier()} logged out.", $user);
            }
        }
    }
}
