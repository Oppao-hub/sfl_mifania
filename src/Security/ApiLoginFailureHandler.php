<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Returns account-status login errors from UserChecker; otherwise delegates to Lexik.
 */
class ApiLoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly AuthenticationFailureHandlerInterface $lexikFailureHandler,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof CustomUserMessageAccountStatusException) {
            $message = trim(strip_tags($exception->getMessage()));

            if ($message !== '') {
                return $this->jsonError($message);
            }
        }

        return $this->lexikFailureHandler->onAuthenticationFailure($request, $exception);
    }

    private function jsonError(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message], Response::HTTP_UNAUTHORIZED);
    }
}
