<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\CustomerPaymentMethodRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class CustomerPaymentMethodsProvider implements ProviderInterface
{
    public function __construct(
        private CustomerPaymentMethodRepository $repository,
        private Security $security,
        private LoggerInterface $logger,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->getCustomer()) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        try {
            return $this->repository->findByCustomerOrdered($user->getCustomer());
        } catch (\Throwable $e) {
            if (!$this->isMissingPaymentMethodTable($e)) {
                throw $e;
            }

            $this->logger->error('customer_payment_method table is missing; run doctrine migrations', [
                'exception' => $e,
            ]);

            throw new ServiceUnavailableHttpException(
                null,
                'Payment methods are temporarily unavailable. Please try again shortly.',
            );
        }
    }

    private function isMissingPaymentMethodTable(\Throwable $e): bool
    {
        if ($e instanceof TableNotFoundException) {
            return true;
        }

        if (str_contains($e->getMessage(), 'customer_payment_method')) {
            return true;
        }

        $previous = $e->getPrevious();
        if ($previous instanceof \Throwable) {
            return $this->isMissingPaymentMethodTable($previous);
        }

        return false;
    }
}
