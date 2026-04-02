<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // This prevents unverified users from probing passwords.
        if (!$user->getIsVerified()) {
            $resendUrl = $this->urlGenerator->generate('app_resend_verification');
            throw new CustomUserMessageAccountStatusException(
                \sprintf('Your account is not verified. <a href="%s" class="underline font-black hover:text-red-400 transition-colors">Click here to resend verification.</a>', $resendUrl)
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // --- THE LOGIC GOES HERE ---

        $status = $user->getStatus()->value; // e.g., 'Deactivated', 'Pending', 'Active'

        if ($status === 'Deactivated') {
            // This message will be shown to the user on the login page
            throw new CustomUserMessageAccountStatusException('Your account has been deactivated. Please contact the administrator.');
        }

        if ($status === 'Pending') {
            throw new CustomUserMessageAccountStatusException('Your account is pending approval.');
        }
    }
}
