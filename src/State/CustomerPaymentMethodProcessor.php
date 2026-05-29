<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\CustomerPaymentMethod;
use App\Entity\User;
use App\Repository\CustomerPaymentMethodRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CustomerPaymentMethodProcessor implements ProcessorInterface
{
    private const WALLET_TYPES = ['paypal', 'google_pay', 'apple_pay'];

    public function __construct(
        private Security $security,
        private CustomerPaymentMethodRepository $repository,
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        #[Autowire(service: 'api_platform.doctrine.orm.state.remove_processor')]
        private ProcessorInterface $removeProcessor,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof Delete) {
            $this->assertOwner($data);

            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        if (!$data instanceof CustomerPaymentMethod || !$operation instanceof Post) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->getCustomer()) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        $customer = $user->getCustomer();
        $providerType = strtolower(trim((string) $data->getProviderType()));

        if ($providerType === '') {
            throw new BadRequestHttpException('Payment method type is required.');
        }

        if (in_array($providerType, self::WALLET_TYPES, true)) {
            $existing = $this->repository->findOneByCustomerAndProvider($customer, $providerType);
            if ($existing) {
                throw new ConflictHttpException('This payment method is already connected.');
            }

            $data->setCustomer($customer);
            $data->setProviderType($providerType);
            $data->setIsConnected(true);

            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        if ($providerType !== 'card') {
            throw new BadRequestHttpException('Unsupported payment method type.');
        }

        $digits = preg_replace('/\D/', '', (string) $data->getCardNumber()) ?? '';
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            throw new BadRequestHttpException('Enter a valid card number.');
        }

        $holder = trim((string) $data->getHolderName());
        if ($holder === '') {
            throw new BadRequestHttpException('Account holder name is required.');
        }

        $month = $data->getExpiryMonth();
        $year = $data->getExpiryYear();
        if (!$month || !$year || $month < 1 || $month > 12) {
            throw new BadRequestHttpException('Enter a valid expiry date.');
        }

        $fullYear = $year < 100 ? 2000 + $year : $year;
        $expiresAt = \DateTimeImmutable::createFromFormat('Y-n-j', sprintf('%d-%d-1', $fullYear, $month));
        if (!$expiresAt || $expiresAt < new \DateTimeImmutable('first day of this month')) {
            throw new BadRequestHttpException('This card has expired.');
        }

        $data->setCustomer($customer);
        $data->setProviderType('card');
        $data->setHolderName($holder);
        $data->setLastFour(substr($digits, -4));
        $data->setCardBrand($this->detectCardBrand($digits));
        $data->setExpiryMonth($month);
        $data->setExpiryYear($fullYear);
        $data->setIsConnected(true);
        $data->setCardNumber(null);

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function assertOwner(mixed $data): void
    {
        if (!$data instanceof CustomerPaymentMethod) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->getCustomer()) {
            throw new AccessDeniedHttpException('Authentication required.');
        }

        if ($data->getCustomer()?->getId() !== $user->getCustomer()->getId()) {
            throw new AccessDeniedHttpException('You cannot modify this payment method.');
        }
    }

    private function detectCardBrand(string $digits): string
    {
        if (str_starts_with($digits, '4')) {
            return 'visa';
        }
        if (preg_match('/^5[1-5]/', $digits) || preg_match('/^2[2-7]/', $digits)) {
            return 'mastercard';
        }
        if (preg_match('/^3[47]/', $digits)) {
            return 'amex';
        }
        if (str_starts_with($digits, '35')) {
            return 'jcb';
        }

        return 'card';
    }
}
