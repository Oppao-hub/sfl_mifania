<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use App\Entity\User;
use App\Service\RewardManager;
use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use App\Repository\OrderRepository;
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
        private RequestStack $requestStack,
        private LoggerInterface $logger,
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
        }

        // Persist the order first
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

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
                $this->cartService->clearCart();
            } catch (\Throwable $exception) {
                $this->logger->error('Failed to clear cart after order creation.', [
                    'orderId' => $data->getId(),
                    'customerId' => $customer->getId(),
                    'exception' => $exception,
                ]);
            }
        }

        return $result;
    }
}
