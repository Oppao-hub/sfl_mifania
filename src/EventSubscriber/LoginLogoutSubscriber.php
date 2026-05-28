<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ActivityLogger;
use App\Service\NotificationPublisher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LoginLogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ActivityLogger $logger,
        private NotificationPublisher $notificationPublisher,
        private UserRepository $userRepository
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

        if ($user instanceof User) {
            $request = $event->getRequest();
            $path = $request->getPathInfo();

            // Google mobile login uses ApiGoogleController (no LoginSuccessEvent).
            // JSON /api/login is covered here — skip JwtAuthenticationSuccessHandler duplicates.
            $isApiLogin = str_starts_with($path, '/api/login');

            $loginMethod = match (true) {
                $isApiLogin => 'Mobile App',
                str_contains($path, 'google') => 'Google OAuth',
                default => 'Login Form',
            };

            $alertTitle = $isApiLogin ? 'Security Alert: Mobile Login' : 'Security Alert: New Login';

            // 1. Notify the User themselves (Security Alert) — once per successful login
            $this->notificationPublisher->send(
                $user,
                $alertTitle,
                "A new login was detected on your account via {$loginMethod}.",
                'app_account', // Link to account settings
                [],
                'security',
                false // Don't flush yet
            );

            // 2. Notify Management (Admins & Staff)
            $management = $this->userRepository->findAllManagement();
            foreach ($management as $manager) {
                if ($manager->getId() === $user->getId()) {
                    continue;
                }

                $this->notificationPublisher->send(
                    $manager,
                    $isApiLogin ? 'Mobile Login Detected' : 'User Login Detected',
                    $isApiLogin
                        ? "User {$user->getEmail()} has logged in via Mobile App."
                        : "User {$user->getEmail()} has logged into the system.",
                    'app_dashboard',
                    [],
                    'system',
                    false // Don't flush yet
                );
            }

            // 3. Log the Activity AND Flush everything (Log + Notifications)
            $this->logger->log(
                'LOGIN',
                "User {$user->getUserIdentifier()} logged in via {$loginMethod}.",
                $user,
                true // Perform Flush here to save everything at once
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
