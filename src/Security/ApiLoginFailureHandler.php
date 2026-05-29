<?php

namespace App\Security;

use App\Entity\Enum\AccountStatus;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Returns account-specific login errors (e.g. deactivated) instead of a generic
 * "Invalid credentials" when we can safely resolve the user by email.
 */
class ApiLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public const DEACTIVATED_MESSAGE = 'Your account has been deactivated. Please contact an admin to reactivate your account.';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthenticationFailureHandlerInterface $lexikFailureHandler,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $exceptionMessage = trim($exception->getMessage());
        if ($exceptionMessage !== '' && str_contains(strtolower($exceptionMessage), 'deactivated')) {
            return $this->jsonError($exceptionMessage);
        }

        $email = $this->extractEmail($request);
        if ($email !== null) {
            $user = $this->userRepository->findOneBy(['email' => $email]);
            if ($user !== null && $user->getStatus() === AccountStatus::Deactivated) {
                return $this->jsonError(self::DEACTIVATED_MESSAGE);
            }
        }

        return $this->lexikFailureHandler->onAuthenticationFailure($request, $exception);
    }

    private function extractEmail(Request $request): ?string
    {
        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return null;
        }

        $email = trim((string) ($payload['email'] ?? $payload['username'] ?? ''));

        return $email !== '' ? $email : null;
    }

    private function jsonError(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message], Response::HTTP_UNAUTHORIZED);
    }
}
