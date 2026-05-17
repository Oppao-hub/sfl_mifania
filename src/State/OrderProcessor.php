<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Order;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OrderProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Order) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        if ($user && $user->getCustomer()) {
            $data->setCustomer($user->getCustomer());
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

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
