<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\ActivityLogger;
use App\Service\LoginNotificationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginLogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogger $logger,
        private LoginNotificationService $loginNotificationService,
    ) {
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

        if (!$user instanceof User) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        // Only create login notifications on explicit login endpoints.
        $isExplicitLoginEndpoint = \in_array($path, [
            '/login',
            '/api/login',
            '/api/login/google',
            '/connect/google/check',
        ], true);

        if (!$isExplicitLoginEndpoint) {
            return;
        }

        $isApiLogin = str_starts_with($path, '/api/login');

        $loginMethod = match (true) {
            $path === '/api/login/google' => 'Google (Mobile App)',
            $isApiLogin => 'Mobile App',
            str_contains($path, 'google') => 'Google OAuth',
            default => 'Login Form',
        };

        if ($isApiLogin) {
            $this->loginNotificationService->notifyMobileLogin($user, false);
        } else {
            $this->loginNotificationService->notifyWebLogin($user, $loginMethod, false);
        }

        $this->logger->log(
            'LOGIN',
            "User {$user->getUserIdentifier()} logged in via {$loginMethod}.",
            $user,
            true,
        );
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
