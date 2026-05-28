<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

#[Route('/api/loyalty-policy')]
class LoyaltyPolicyController extends AbstractController
{
    public function __construct(
        private readonly int $loyaltyPointsPerCurrency,
        private readonly float $loyaltyMinOrderForRedemption,
        private readonly float $loyaltyMaxRedemptionPercentage
    ) {}

    #[Route('', name: 'api_loyalty_policy_get', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'pointsPerCurrency' => max(1, $this->loyaltyPointsPerCurrency),
            'minOrderForRedemption' => (float) $this->loyaltyMinOrderForRedemption,
            'maxRedemptionPercentage' => (float) $this->loyaltyMaxRedemptionPercentage,
        ]);
    }
}
