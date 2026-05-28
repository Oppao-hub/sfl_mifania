<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\CustomerAddress;
use App\Entity\User;
use App\Service\CustomerAddressService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CustomerAddressProcessor implements ProcessorInterface
{
    public function __construct(
        private CustomerAddressService $addressService,
        private Security $security,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof CustomerAddress) {
            if ($operation instanceof Delete) {
                return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
            }

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->getCustomer()) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $customer = $user->getCustomer();

        if ($operation instanceof Post) {
            $data->setCustomer($customer);
            if (!$data->getRecipientFirstName()) {
                $data->setRecipientFirstName($customer->getFirstName());
            }
            if (!$data->getRecipientLastName()) {
                $data->setRecipientLastName($customer->getLastName());
            }
            if (!$data->getContactNumber()) {
                $data->setContactNumber($customer->getContactNumber());
            }

            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            $this->addressService->handlePostCreate($data);

            return $result;
        }

        if ($operation instanceof Patch) {
            if ($data->getCustomer()?->getId() !== $customer->getId()) {
                throw new AccessDeniedHttpException('You cannot modify this address.');
            }

            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            $this->addressService->handlePatch($data);

            return $result;
        }

        if ($operation instanceof Delete) {
            if ($data->getCustomer()?->getId() !== $customer->getId()) {
                throw new AccessDeniedHttpException('You cannot delete this address.');
            }

            $this->addressService->handleDelete($data);

            return null;
        }

        throw new BadRequestHttpException('Unsupported operation.');
    }
}
