<?php

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $balance = '0.00';

    #[ORM\Column]
    private ?int $rewardPoints = 0;

    /**
     * @var Collection<int, WalletTransaction>
     */
    #[ORM\OneToMany(targetEntity: WalletTransaction::class, mappedBy: 'wallet', orphanRemoval: true)]
    private Collection $walletTransactions;

    #[ORM\OneToOne(inversedBy: 'wallet', cascade: ['persist', 'remove'])]
    private ?Customer $customer = null;

    public function __construct()
    {
        $this->walletTransactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getRewardPoints(): ?int
    {
        return $this->rewardPoints;
    }

    public function setRewardPoints(int $rewardPoints): static
    {
        $this->rewardPoints = $rewardPoints;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection<int, WalletTransaction>
     */
    public function getWalletTransactions(): Collection
    {
        return $this->walletTransactions;
    }


    public function addWalletTransaction(WalletTransaction $walletTransaction): static
    {
        if (!$this->walletTransactions->contains($walletTransaction)) {
            $this->walletTransactions->add($walletTransaction);
            $walletTransaction->setWallet($this);
        }

        return $this;
    }

    public function removeWalletTransaction(WalletTransaction $walletTransaction): static
    {
        if ($this->walletTransactions->removeElement($walletTransaction)) {
            // set the owning side to null (unless already changed)
            if ($walletTransaction->getWallet() === $this) {
                $walletTransaction->setWallet(null);
            }
        }

        return $this;
    }

    public function deposit(float $amount, string $description = 'Deposit'): WalletTransaction
    {
        // Safely cast to float for math, then back to string for the database
        $this->balance = (string) ((float)$this->balance + $amount);

        $transaction = new WalletTransaction();
        $transaction->setWallet($this);
        $transaction->setAmount((string) $amount); // Explicit cast to string
        $transaction->setType('deposit');
        $transaction->setDescription($description);
        $transaction->setCreatedAt(new \DateTimeImmutable());

        return $transaction;
    }

    public function withdraw(float $amount, string $description = 'Withdrawal'): ?WalletTransaction
    {
        if ((float)$this->balance < $amount) {
            throw new \InvalidArgumentException('Insufficient balance for withdrawal.');
        }

        $this->balance = (string) ((float)$this->balance - $amount);

        $transaction = new WalletTransaction();
        $transaction->setWallet($this);
        $transaction->setAmount((string) -$amount); // Explicit cast to string
        $transaction->setType('withdrawal');
        $transaction->setDescription($description);
        $transaction->setCreatedAt(new \DateTimeImmutable());

        return $transaction;
    }

    public function addRewardPoints(int $points, string $description = 'Reward Earned'): WalletTransaction
    {
        $this->rewardPoints += $points;

        $transaction = new WalletTransaction();
        $transaction->setWallet($this);
        $transaction->setAmount('0.00'); // Explicit cast
        $transaction->setType('reward');
        $transaction->setDescription($description . " ({$points} points)");
        $transaction->setCreatedAt(new \DateTimeImmutable());

        return $transaction;
    }
}
