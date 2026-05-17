<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Redemption;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpKernelException;

final readonly class RedemptionProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security
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
            throw new BadRequestHttpKernelException('Authenticated user must be a customer.');
        }

        $data->setCustomer($customer);
        $data->setRedeemedAt(new \DateTimeImmutable());
        $data->setStatus('PENDING');

        $reward = $data->getReward();
        if (!$reward) {
            throw new BadRequestHttpKernelException('Reward is required.');
        }

        $wallet = $customer->getWallet();
        if (!$wallet || $wallet->getRewardPoints() < $reward->getPointsRequired()) {
            throw new BadRequestHttpKernelException('Insufficient reward points.');
        }

        // Deduct points
        $data->setPointSpent($reward->getPointsRequired());
        $wallet->setRewardPoints($wallet->getRewardPoints() - $reward->getPointsRequired());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
