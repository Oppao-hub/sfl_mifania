<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\DeliveryOptionsCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/delivery-options')]
class DeliveryOptionsController extends AbstractController
{
    #[Route('', name: 'api_delivery_options_get', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $options = array_map(
            static fn (array $option) => [
                'id' => $option['id'],
                'name' => $option['name'],
                'estimate' => $option['estimate'],
                'fee' => $option['fee'],
                'description' => $option['description'],
            ],
            DeliveryOptionsCatalog::all(),
        );

        return $this->json([
            '@context' => '/api/contexts/DeliveryOption',
            '@type' => 'hydra:Collection',
            'hydra:member' => $options,
            'hydra:totalItems' => count($options),
        ]);
    }
}
