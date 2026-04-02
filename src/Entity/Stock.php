<?php

namespace App\Entity;

use App\Entity\Enum\StockStatus;
use App\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'You must specify a stock quantity.')]
    #[Assert\PositiveOrZero(message: 'Quantity cannot be negative.')]
    private ?int $quantity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Location name is too long.')]
    private ?string $location = null;

    #[ORM\Column(length: 50, enumType: StockStatus::class)]
    private ?StockStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Assert\NotBlank(message: 'You must select an associated product.')]
    private ?Product $product = null;

    #[ORM\ManyToOne(inversedBy: 'stocks')]
    private ?User $addedBy = null;

    #[ORM\ManyToOne]
    private ?User $updatedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateStatus(): void
    {
        $qty = $this->quantity ?? 0;

        if ($qty <= 0) {
            $this->status = StockStatus::OUT_OF_STOCK;
        } elseif ($qty < 50) {
            $this->status = StockStatus::LOW_STOCK;
        } else {
            $this->status = StockStatus::IN_STOCK;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(?int $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getStatus(): ?StockStatus
    {
        return $this->status;
    }

    public function setStatus(?StockStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getAddedBy(): ?User
    {
        return $this->addedBy;
    }

    public function setAddedBy(?User $addedBy): static
    {
        $this->addedBy = $addedBy;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;
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

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
