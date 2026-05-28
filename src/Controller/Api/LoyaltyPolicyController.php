<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use App\Service\RewardManager;

#[Route('/api/loyalty-policy')]
class LoyaltyPolicyController extends AbstractController
{
    #[Route('', name: 'api_loyalty_policy_get', methods: ['GET'])]
    public function index(#[CurrentUser] ?User $user, RewardManager $rewardManager): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'pointsPerCurrency' => $rewardManager->getPointsPerCurrency(),
            'minOrderForRedemption' => $rewardManager->getMinOrderForRedemption(),
            'maxRedemptionPercentage' => $rewardManager->getMaxRedemptionPercentage(),
        ]);
    }
}
