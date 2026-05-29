<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\PaymentStatus;
use App\Entity\Order;
use App\Entity\User;
use App\Service\CartService;
use App\Service\RewardManager;
use App\Service\SocketIoPublisher;
use App\Service\WalletManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Repository\OrderRepository;
use App\Repository\CustomerAddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Psr\Log\LoggerInterface;

final readonly class OrderProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private RewardManager $rewardManager,
        private CartService $cartService,
        private OrderRepository $orderRepository,
        private CustomerAddressRepository $customerAddressRepository,
        private RequestStack $requestStack,
        private LoggerInterface $logger,
        private WalletManager $walletManager,
        private EntityManagerInterface $entityManager,
        private SocketIoPublisher $socketIoPublisher,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Order) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        $customer = $user?->getCustomer();

        if ($customer) {
            $data->setCustomer($customer);

            $selectedAddress = $data->getCustomerAddress();
            if ($selectedAddress) {
                if ($selectedAddress->getCustomer()?->getId() !== $customer->getId()) {
                    throw new BadRequestHttpException('Invalid shipping address for this customer.');
                }
                $data->applyShippingSnapshotFromAddress($selectedAddress);
            } elseif ($defaultAddress = $this->customerAddressRepository->findDefaultForCustomer($customer)) {
                $data->applyShippingSnapshotFromAddress($defaultAddress);
            }
        }

        $request = $this->requestStack->getCurrentRequest();
        $idempotencyKey = trim((string) ($request?->headers->get('Idempotency-Key') ?? ''));
        if ($idempotencyKey !== '' && $customer) {
            $existingOrder = $this->orderRepository->findOneByCustomerAndIdempotencyKey($customer, $idempotencyKey);
            if ($existingOrder) {
                return $existingOrder;
            }
            $data->setIdempotencyKey($idempotencyKey);
        }

        // Validate and deduct stock
        foreach ($data->getOrderItems() as $item) {
            $product = $item->getProduct();
            if (!$product) {
                throw new BadRequestHttpException('Each order item must include a valid product.');
            }

            if ($product->getTotalStockQuantity() < $item->getQuantity()) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Sorry, "%s" is out of stock or has insufficient quantity.', $product->getName())
                );
            }

            try {
                $product->deductStockQuantity($item->getQuantity());
            } catch (\Throwable $exception) {
                throw new UnprocessableEntityHttpException(
                    sprintf('Sorry, "%s" is out of stock or has insufficient quantity.', $product->getName()),
                    $exception
                );
            }
        }

        $originalAmount = (float) ($data->getTotalAmount() ?? '0');
        if ($originalAmount <= 0) {
            throw new BadRequestHttpException('Order amount must be greater than zero.');
        }

        $data->setOriginalAmount(number_format($originalAmount, 2, '.', ''));

        $isWalletPayment = $data->getPaymentMethod() === PaymentMethod::WALLET;

        $result = $this->entityManager->wrapInTransaction(function () use (
            $data,
            $operation,
            $uriVariables,
            $context,
            $customer,
            $originalAmount,
            $isWalletPayment,
        ) {
            if ($customer) {
                $wallet = $customer->getWallet();
                $requestedPoints = max(0, (int) ($data->getPointsRedeemed() ?? 0));
                if ($requestedPoints > 0) {
                    if (!$wallet || $wallet->getRewardPoints() < $requestedPoints) {
                        throw new BadRequestHttpException('Insufficient reward points.');
                    }

                    $requestedDiscount = (float) $this->rewardManager->currencyDiscountFromPoints($requestedPoints);
                    $policyMaxDiscount = $this->rewardManager->maxDiscountForOrder($originalAmount);
                    if ($policyMaxDiscount <= 0) {
                        throw new BadRequestHttpException('Order amount is below the minimum for loyalty redemption.');
                    }
                    $cappedDiscount = min($requestedDiscount, $originalAmount, $policyMaxDiscount);
                    $effectivePoints = $this->rewardManager->pointsFromDiscount($cappedDiscount);

                    if ($effectivePoints <= 0 || !$this->rewardManager->consumePoints($customer, $effectivePoints, null, 'REDEEMED_ORDER')) {
                        throw new BadRequestHttpException('Unable to apply reward points.');
                    }

                    $data->setPointsRedeemed($effectivePoints);
                    $data->setDiscountAmount(number_format($cappedDiscount, 2, '.', ''));
                    $data->setTotalAmount(number_format(max(0, $originalAmount - $cappedDiscount), 2, '.', ''));
                } else {
                    $data->setPointsRedeemed(0);
                    $data->setDiscountAmount('0.00');
                }

                if ($isWalletPayment) {
                    $wallet = $customer->getWallet();
                    $payableAmount = (float) ($data->getTotalAmount() ?? '0');
                    if ($payableAmount <= 0) {
                        throw new BadRequestHttpException('Order amount must be greater than zero.');
                    }
                    if (!$wallet) {
                        throw new BadRequestHttpException('Wallet not found.');
                    }
                    if ((float) $wallet->getBalance() < $payableAmount) {
                        throw new BadRequestHttpException('Insufficient wallet balance.');
                    }
                }
            } elseif ($isWalletPayment) {
                throw new BadRequestHttpException('Wallet payment requires a customer account.');
            }

            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

            if ($isWalletPayment && $customer && $result instanceof Order) {
                $wallet = $customer->getWallet();
                if (!$wallet) {
                    throw new BadRequestHttpException('Wallet not found.');
                }

                $payableAmount = (float) ($result->getTotalAmount() ?? '0');
                $this->walletManager->chargeForOrder($wallet, $result, $payableAmount);
                $result->setPaymentStatus(PaymentStatus::PAID);
                $this->entityManager->flush();
            }

            return $result;
        });

        if ($customer) {
            if ($data->getRewardPoints() > 0) {
                try {
                    $this->rewardManager->earnPointsFromOrder($customer, $data, $data->getRewardPoints());
                } catch (\Throwable $exception) {
                    $this->logger->error('Failed to award loyalty points after order creation.', [
                        'orderId' => $data->getId(),
                        'customerId' => $customer->getId(),
                        'exception' => $exception,
                    ]);
                }
            }

            try {
                if ($result instanceof Order) {
                    $this->cartService->removeOrderItemsFromCart($result);
                }
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to remove purchased items from cart after order creation.', [
                    'orderId' => $data->getId(),
                    'customerId' => $customer->getId(),
                    'exception' => $exception,
                ]);
            }

            $user = $customer->getUser();
            if ($user && $result instanceof Order && $result->getId()) {
                $orderId = (string) $result->getId();
                $this->socketIoPublisher->publish($user->getId(), 'dashboard_refresh', [
                    'entity' => 'order',
                    'action' => 'created',
                    'orderId' => $orderId,
                    'message' => sprintf('Order #%s placed successfully.', $orderId),
                ]);
                $this->socketIoPublisher->publish($user->getId(), 'dashboard_refresh', [
                    'entity' => 'cart',
                    'action' => 'updated',
                ]);
            }
        }

        return $result;
    }
}
