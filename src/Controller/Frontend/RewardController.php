<?php

namespace App\Controller\Frontend;

use App\Entity\Reward;
use App\Service\RewardManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[IsGranted('ROLE_CUSTOMER')]
class RewardController extends AbstractController
{
    #[Route('/rewards/claim/{id}', name: 'app_frontend_reward_claim', methods: ['POST'])]
    public function claim(Reward $reward, RewardManager $rewardManager, #[CurrentUser] $user): Response
    {
        $customer = $user->getCustomer();

        // Use the service to attempt the purchase
        $success = $rewardManager->claimReward($customer, $reward);

        if ($success) {
            $this->addFlash('success', 'Congratulations! You claimed: ' . $reward->getName());
        } else {
            $this->addFlash('error', 'You do not have enough points for this reward.');
        }

        return $this->redirectToRoute('app_account');
    }
}
