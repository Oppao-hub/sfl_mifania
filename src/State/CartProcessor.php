<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Cart;
use App\Entity\User;
use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CartProcessor implements ProcessorInterface
{
    private CartService $cartService;
    private Security $security;
    private ProcessorInterface $persistProcessor;

    public function __construct(
        CartService $cartService, 
        Security $security, 
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        ProcessorInterface $persistProcessor
    ) {
        $this->cartService = $cartService;
        $this->security = $security;
        $this->persistProcessor = $persistProcessor;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Cart) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        $customer = ($user instanceof User) ? $user->getCustomer() : null;

        if (!$customer) {
            throw new \Exception('Authentication required.');
        }

        // Handle POST (Create new cart)
        if ($operation instanceof Post) {
            $data->setCustomer($customer);
            // Default isMain to false for new collections created via API
            if ($data->isMain()) {
                $this->cartService->switchMainCart($data);
            }
        }

        // Handle PATCH (Update or Switch main)
        if ($operation instanceof Patch) {
             if ($data->isMain()) {
                 $this->cartService->switchMainCart($data);
             }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
