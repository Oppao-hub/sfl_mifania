<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack,
    ) {
    }

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        if (!$user->getIsVerified()) {
            if ($this->isApiRequest()) {
                throw new CustomUserMessageAccountStatusException(
                    'Your account is not verified. Please check your email or request a new verification link.',
                );
            }

            $resendUrl = $this->urlGenerator->generate('app_resend_verification');
            throw new CustomUserMessageAccountStatusException(
                \sprintf('Your account is not verified. <a href="%s" class="underline font-black hover:text-red-400 transition-colors">Click here to resend verification.</a>', $resendUrl),
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        $status = $user->getStatus()->value;

        if ($status === 'Deactivated') {
            throw new CustomUserMessageAccountStatusException(
                'Your account has been deactivated. Please contact an admin to reactivate your account.',
            );
        }

        if ($status === 'Pending') {
            throw new CustomUserMessageAccountStatusException('Your account is pending approval.');
        }
    }

    private function isApiRequest(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request !== null && str_starts_with($request->getPathInfo(), '/api/');
    }
}
