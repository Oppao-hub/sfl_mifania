<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Redemption;
use App\Entity\User;
use App\Service\RewardManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class RedemptionProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private RewardManager $rewardManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Redemption) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        /** @var User $user */
        $user = $this->security->getUser();
        $customer = $user?->getCustomer();

        if (!$customer) {
            throw new BadRequestHttpException('Authenticated user must be a customer.');
        }

        $data->setCustomer($customer);
        $data->setRedeemedAt(new \DateTimeImmutable());
        $data->setStatus('PENDING');

        $reward = $data->getReward();
        if (!$reward) {
            throw new BadRequestHttpException('Reward is required.');
        }

        $wallet = $customer->getWallet();
        $pointsRequired = $reward->getPointsRequired() ?? 0;
        if (!$wallet || $wallet->getRewardPoints() < $pointsRequired) {
            throw new BadRequestHttpException('Insufficient reward points.');
        }

        $data->setPointSpent($pointsRequired);
        if (!$this->rewardManager->consumePoints($customer, $pointsRequired)) {
            throw new BadRequestHttpException('Unable to redeem points.');
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
