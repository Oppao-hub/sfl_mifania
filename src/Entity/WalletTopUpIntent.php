<?php

namespace App\Entity;

use App\Repository\WalletTopUpIntentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WalletTopUpIntentRepository::class)]
#[ORM\Table(name: 'wallet_top_up_intent')]
class WalletTopUpIntent
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $prepareToken = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $paypalOrderId = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getPrepareToken(): ?string
    {
        return $this->prepareToken;
    }

    public function setPrepareToken(string $prepareToken): static
    {
        $this->prepareToken = $prepareToken;

        return $this;
    }

    public function getPaypalOrderId(): ?string
    {
        return $this->paypalOrderId;
    }

    public function setPaypalOrderId(?string $paypalOrderId): static
    {
        $this->paypalOrderId = $paypalOrderId;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function markCompleted(): static
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completedAt = new \DateTimeImmutable();

        return $this;
    }
}
