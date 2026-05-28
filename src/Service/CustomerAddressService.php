<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\CustomerAddress;
use App\Repository\CustomerAddressRepository;
use Doctrine\ORM\EntityManagerInterface;

class CustomerAddressService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerAddressRepository $addressRepository,
    ) {
    }

    /**
     * @return CustomerAddress[]
     */
    public function getCustomerAddresses(Customer $customer): array
    {
        return $this->addressRepository->findByCustomerOrdered($customer);
    }

    public function ensureDefaultAddress(Customer $customer): void
    {
        if ($this->addressRepository->countForCustomer($customer) > 0) {
            return;
        }

        if (!$customer->getAddress()) {
            return;
        }

        $address = new CustomerAddress();
        $address->setCustomer($customer);
        $address->setLabel('Home');
        $address->setRecipientFirstName($customer->getFirstName());
        $address->setRecipientLastName($customer->getLastName());
        $address->setContactNumber($customer->getContactNumber());
        $address->setAddress($customer->getAddress());
        $address->setCity($customer->getCity());
        $address->setState($customer->getState());
        $address->setCountry($customer->getCountry());
        $address->setPostalCode($customer->getPostalCode());
        $address->setIsDefault(true);
        $address->setHasPinpoint(true);

        $this->entityManager->persist($address);
        $this->entityManager->flush();
    }

    public function applyDefaultAddress(CustomerAddress $address): void
    {
        $customer = $address->getCustomer();
        if (!$customer) {
            return;
        }

        $this->clearDefaultFlags($customer, $address->getId());
        $address->setIsDefault(true);
        $this->syncCustomerFromAddress($customer, $address);
    }

    public function syncCustomerFromAddress(Customer $customer, CustomerAddress $address): void
    {
        $customer->setAddress($address->getAddress());
        $customer->setCity($address->getCity());
        $customer->setState($address->getState());
        $customer->setCountry($address->getCountry());
        $customer->setPostalCode($address->getPostalCode());
        if ($address->getContactNumber()) {
            $customer->setContactNumber($address->getContactNumber());
        }
    }

    public function handlePostCreate(CustomerAddress $address): void
    {
        $customer = $address->getCustomer();
        if (!$customer) {
            return;
        }

        if ($this->addressRepository->countForCustomer($customer) === 1) {
            $address->setIsDefault(true);
        }

        if ($address->isDefault()) {
            $this->applyDefaultAddress($address);
        }

        $this->entityManager->flush();
    }

    public function handlePatch(CustomerAddress $address): void
    {
        if ($address->isDefault()) {
            $this->applyDefaultAddress($address);
            $this->entityManager->flush();
        }
    }

    public function handleDelete(CustomerAddress $address): void
    {
        $customer = $address->getCustomer();
        if (!$customer) {
            return;
        }

        $wasDefault = $address->isDefault();
        $this->entityManager->remove($address);
        $this->entityManager->flush();

        if (!$wasDefault) {
            return;
        }

        $remaining = $this->addressRepository->findByCustomerOrdered($customer);
        if ($remaining === []) {
            $customer->setAddress(null);
            $customer->setCity(null);
            $customer->setState(null);
            $customer->setCountry(null);
            $customer->setPostalCode(null);
            $this->entityManager->flush();

            return;
        }

        $this->applyDefaultAddress($remaining[0]);
        $this->entityManager->flush();
    }

    private function clearDefaultFlags(Customer $customer, ?int $exceptId = null): void
    {
        foreach ($this->addressRepository->findByCustomerOrdered($customer) as $existing) {
            if ($exceptId !== null && $existing->getId() === $exceptId) {
                continue;
            }
            $existing->setIsDefault(false);
        }
    }
}
