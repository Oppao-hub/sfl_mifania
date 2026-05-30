<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;

/**
 * Creates inbox + push notifications when a user signs in.
 */
final class LoginNotificationService
{
    public function __construct(
        private NotificationPublisher $notificationPublisher,
        private UserRepository $userRepository,
    ) {}

    public function notifyMobileLogin(User $user, bool $flush = true): void
    {
        $this->notifyLogin($user, 'Mobile App', true, $flush);
    }

    public function notifyWebLogin(User $user, string $loginMethod, bool $flush = true): void
    {
        $this->notifyLogin($user, $loginMethod, false, $flush);
    }

    private function notifyLogin(User $user, string $loginMethod, bool $isMobile, bool $flush): void
    {
        $alertTitle = $isMobile ? 'Security Alert: Mobile Login' : 'Security Alert: New Login';

        $this->notificationPublisher->send(
            $user,
            $alertTitle,
            "A new login was detected on your account via {$loginMethod}.",
            'app_account',
            [],
            'security',
            $flush,
        );

        $management = $this->userRepository->findAllManagement();
        foreach ($management as $manager) {
            if ($manager->getId() === $user->getId()) {
                continue;
            }

            $this->notificationPublisher->send(
                $manager,
                $isMobile ? 'Mobile Login Detected' : 'User Login Detected',
                $isMobile
                    ? "User {$user->getEmail()} has logged in via Mobile App."
                    : "User {$user->getEmail()} has logged into the system.",
                'app_dashboard',
                [],
                'system',
                $flush,
            );
        }
    }
}
