<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use App\Service\CustomerAddressService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CustomerAddressesProvider implements ProviderInterface
{
    public function __construct(
        private CustomerAddressService $addressService,
        private Security $security,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->getCustomer()) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $customer = $user->getCustomer();
        $this->addressService->ensureDefaultAddress($customer);

        return $this->addressService->getCustomerAddresses($customer);
    }
}
