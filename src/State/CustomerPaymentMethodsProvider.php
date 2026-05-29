<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Repository\CustomerPaymentMethodRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CustomerPaymentMethodsProvider implements ProviderInterface
{
    public function __construct(
        private CustomerPaymentMethodRepository $repository,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->getCustomer()) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        return $this->repository->findByCustomerOrdered($user->getCustomer());
    }
}
