<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\Wallet;
use App\Entity\WalletTransaction;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;

class WalletManager
{
    public const MAX_AMOUNT = 99999.99;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomerRepository $customerRepository,
    ) {
    }

    public function topUp(Wallet $wallet, float $amount, ?string $description = null): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        $transaction = $wallet->deposit($amount, $description ?: 'Wallet top-up');
        $wallet->addWalletTransaction($transaction);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }

    /**
     * @return array{withdrawal: WalletTransaction, deposit: WalletTransaction}
     */
    public function transfer(Wallet $senderWallet, string $recipientEmail, float $amount, ?string $note = null): array
    {
        $this->assertPositiveAmount($amount);

        $sender = $senderWallet->getCustomer();
        if (!$sender) {
            throw new \InvalidArgumentException('Sender wallet has no linked customer.');
        }

        $recipient = $this->customerRepository->findOneByUserEmail($recipientEmail);
        if (!$recipient) {
            throw new \InvalidArgumentException('No customer found with that email address.');
        }

        if ($recipient->getId() === $sender->getId()) {
            throw new \InvalidArgumentException('You cannot transfer funds to your own wallet.');
        }

        $recipientWallet = $recipient->getWallet();
        if (!$recipientWallet) {
            $recipientWallet = new Wallet();
            $recipientWallet->setCustomer($recipient);
            $recipientWallet->setBalance('0.00');
            $recipientWallet->setRewardPoints(0);
            $recipient->setWallet($recipientWallet);
            $this->entityManager->persist($recipientWallet);
        }

        $recipientLabel = $this->formatCustomerLabel($recipient);
        $senderLabel = $this->formatCustomerLabel($sender);

        $withdrawDescription = $note
            ? sprintf('%s (to %s)', $note, $recipientLabel)
            : sprintf('Transfer to %s', $recipientLabel);

        $depositDescription = $note
            ? sprintf('%s (from %s)', $note, $senderLabel)
            : sprintf('Transfer from %s', $senderLabel);

        return $this->entityManager->wrapInTransaction(function () use (
            $senderWallet,
            $recipientWallet,
            $amount,
            $withdrawDescription,
            $depositDescription,
        ): array {
            $withdrawal = $senderWallet->withdraw($amount, $withdrawDescription);
            if (!$withdrawal) {
                throw new \InvalidArgumentException('Unable to process withdrawal.');
            }
            $senderWallet->addWalletTransaction($withdrawal);

            $deposit = $recipientWallet->deposit($amount, $depositDescription);
            $recipientWallet->addWalletTransaction($deposit);

            $this->entityManager->persist($withdrawal);
            $this->entityManager->persist($deposit);

            return [
                'withdrawal' => $withdrawal,
                'deposit' => $deposit,
            ];
        });
    }

    private function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }

        if ($amount > self::MAX_AMOUNT) {
            throw new \InvalidArgumentException(sprintf('Amount cannot exceed ₱%s.', number_format(self::MAX_AMOUNT, 2)));
        }
    }

    private function formatCustomerLabel(Customer $customer): string
    {
        $email = $customer->getUser()?->getEmail();
        if ($email) {
            return $email;
        }

        return trim(sprintf('%s %s', $customer->getFirstName() ?? '', $customer->getLastName() ?? '')) ?: 'Customer';
    }
}
