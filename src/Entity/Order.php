<?php

namespace App\Entity;

use App\Entity\Enum\PaymentStatus;
use App\Entity\Enum\PaymentMethod;
use App\Entity\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Customer $customer = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $orderDate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(length: 50, enumType: PaymentMethod::class)]
    private ?PaymentMethod $paymentMethod = PaymentMethod::Cash;

    #[ORM\Column(length: 50, enumType: PaymentStatus::class)]
    private ?PaymentStatus $paymentStatus = PaymentStatus::Pending;

    #[ORM\Column(length: 50, enumType: OrderStatus::class)]
    private ?OrderStatus $orderStatus = OrderStatus::Pending;

    #[Assert\PositiveOrZero(message: 'Reward points earned must be a positive number.')]
    #[ORM\Column]
    private ?int $rewardPointsEarned = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'customerOrder', cascade: ['persist', 'remove'])]
    private Collection $orderItems;

    /**
     * @var Collection<int, RewardTransaction>
     */
    #[ORM\OneToMany(targetEntity: RewardTransaction::class, mappedBy: 'customerOrder', cascade: ['persist', 'remove'])]
    private Collection $rewardTransactions;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->rewardTransactions = new ArrayCollection();
        $this->orderDate = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $this->updatedAt ?? $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getOrderDate(): ?\DateTime
    {
        return $this->orderDate;
    }
    public function setOrderDate(\DateTime $orderDate): static
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }
    public function setTotalAmount(string $totalAmount): static
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

    public function getRewardPointsEarned(): ?int
    {
        return $this->rewardPointsEarned;
    }
    public function setRewardPointsEarned(int $rewardPointsEarned): static
    {
        $this->rewardPointsEarned = $rewardPointsEarned;
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
            $orderItem->setCustomerOrder($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem) && $orderItem->getCustomerOrder() === $this) {
            $orderItem->setCustomerOrder(null);
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
            $rewardTransaction->setCustomerOrder($this);
        }
        return $this;
    }

    public function removeRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if ($this->rewardTransactions->removeElement($rewardTransaction) && $rewardTransaction->getCustomerOrder() === $this) {
            $rewardTransaction->setCustomerOrder(null);
        }
        return $this;
    }
}
