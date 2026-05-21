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
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private RewardManager $rewardManager,
        private CartService $cartService,
        private EntityManagerInterface $entityManager
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
