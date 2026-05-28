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

final readonly class OrderProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private RewardManager $rewardManager,
        private CartService $cartService,
        private OrderRepository $orderRepository,
        private RequestStack $requestStack
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
            if ($product) {
                if ($product->getTotalStockQuantity() < $item->getQuantity()) {
                    throw new \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException(
                        sprintf('Sorry, "%s" is out of stock or has insufficient quantity.', $product->getName())
                    );
                }
                $product->deductStockQuantity($item->getQuantity());
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
                $effectivePoints = (int) floor($cappedDiscount * RewardManager::POINTS_PER_CURRENCY);

                if ($effectivePoints <= 0 || !$this->rewardManager->consumePoints($customer, $effectivePoints)) {
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

        if ($customer && $data->getRewardPoints() > 0) {
            // Award points and clear cart after successful persistence
            $this->rewardManager->earnPointsFromOrder($customer, $data, $data->getRewardPoints());
            $this->cartService->clearCart();
        }

        return $result;
    }
}
