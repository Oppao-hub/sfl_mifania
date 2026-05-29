<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Rate-limits sensitive public API auth endpoints by client IP.
 */
final class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactory $authLoginLimiter,
        private RateLimiterFactory $authRegisterLimiter,
        private RateLimiterFactory $authPasswordResetLimiter,
        private RateLimiterFactory $authResendVerificationLimiter,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getMethod() !== 'POST') {
            return;
        }

        $limiter = match ($request->getPathInfo()) {
            '/api/login', '/api/login/google' => $this->authLoginLimiter,
            '/api/register' => $this->authRegisterLimiter,
            '/api/reset-password/request' => $this->authPasswordResetLimiter,
            '/api/resend-verification' => $this->authResendVerificationLimiter,
            default => null,
        };

        if ($limiter === null) {
            return;
        }

        $clientIp = $request->getClientIp() ?? 'unknown';
        $rateLimit = $limiter->create($clientIp)->consume();

        if ($rateLimit->isAccepted()) {
            return;
        }

        $retryAfter = $rateLimit->getRetryAfter();
        $seconds = max(1, $retryAfter->getTimestamp() - time());

        $event->setResponse(new JsonResponse([
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $seconds,
        ], Response::HTTP_TOO_MANY_REQUESTS, [
            'Retry-After' => (string) $seconds,
        ]));
    }
}
