<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Enum\PaymentStatus;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['order:read']],
    denormalizationContext: ['groups' => ['order:write']]
)]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['order:read'])]
    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Assert\NotNull(message: 'Please select a customer for this order.')] // <-- ADDED
    private ?Customer $customer = null;

    #[Groups(['order:read'])]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Total amount is required.')] // <-- ADDED
    #[Assert\PositiveOrZero(message: 'Total amount cannot be negative.')] // <-- ADDED
    #[Assert\Type(type: 'numeric', message: 'Total amount must be a valid number.')] // <-- ADDED
    private ?string $totalAmount = null;

    #[Groups(['order:read', 'order:write'])]
    #[ORM\Column(length: 50, enumType: PaymentMethod::class)]
    private ?PaymentMethod $paymentMethod = PaymentMethod::CASH;

    #[Groups(['order:read', 'order:write'])]
    #[ORM\Column(length: 50, enumType: PaymentStatus::class)]
    private ?PaymentStatus $paymentStatus = PaymentStatus::PENDING;

    #[Groups(['order:read', 'order:write'])]
    #[ORM\Column(length: 50, enumType: OrderStatus::class)]
    private ?OrderStatus $orderStatus = OrderStatus::PENDING;

    #[Groups(['order:read', 'order:write'])]
    #[Assert\PositiveOrZero(message: 'Reward points earned must be a positive number.')]
    #[ORM\Column]
    private ?int $rewardPoints = 0;

    #[Groups(['order:read'])]
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[Groups(['order:read'])]
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    #[Assert\Count(min: 1, minMessage: 'An order must contain at least one product.')] // <-- ADDED
    #[Assert\Valid]
    private Collection $orderItems;

    /**
     * @var Collection<int, RewardTransaction>
     */
    #[Groups(['order:read', 'order:write'])]
    #[ORM\OneToMany(targetEntity: RewardTransaction::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private Collection $rewardTransactions;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->rewardTransactions = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $this->updatedAt ?? $now;

        // Auto-calculate reward points: 1 point per 50 pesos
        if ($this->totalAmount) {
            $this->rewardPoints = (int) floor((float)$this->totalAmount / 50);
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();

        // Recalculate if amount changes
        if ($this->totalAmount) {
            $this->rewardPoints = (int) floor((float)$this->totalAmount / 50);
        }
    }

    // --- getters and setters below ---

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
public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }
    public function setPaymentMethod(PaymentMethod $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    public function getPaymentStatus(): ?PaymentStatus
    {
        return $this->paymentStatus;
    }
    public function setPaymentStatus(PaymentStatus $paymentStatus): static
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function getOrderStatus(): ?OrderStatus
    {
        return $this->orderStatus;
    }
    public function setOrderStatus(OrderStatus $orderStatus): static
    {
        $this->orderStatus = $orderStatus;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setOrder($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem) && $orderItem->getOrder() === $this) {
            $orderItem->setOrder(null);
        }
        return $this;
    }

    public function getRewardTransactions(): Collection
    {
        return $this->rewardTransactions;
    }

    public function addRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if (!$this->rewardTransactions->contains($rewardTransaction)) {
            $this->rewardTransactions->add($rewardTransaction);
            $rewardTransaction->setOrder($this);
        }
        return $this;
    }

    public function removeRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if ($this->rewardTransactions->removeElement($rewardTransaction) && $rewardTransaction->getOrder() === $this) {
            $rewardTransaction->setOrder(null);
        }
        return $this;
    }
}
