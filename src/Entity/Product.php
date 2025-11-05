<?php

namespace App\Entity;

use App\Entity\Enum\StockStatus;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Product name cannot be empty.")]
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'products', targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(length: 100)]
    private ?string $material = null;


    #[ORM\Column(length: 10)]
    private ?string $size = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $color = null;

    #[Assert\PositiveOrZero(message: "Price must be positive number.")]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[Assert\PositiveOrZero(message: "Price must be positive number.")]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $cost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?QRTag $qrTag = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = 'default.png';

    #[Assert\PositiveOrZero(message: "Points must be positive number.")]
    #[ORM\Column]
    private ?int $points = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ecoInfo = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'product')]
    private Collection $orderItems;

    /**
     * @var Collection<int, Stock>
     */
    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $stocks;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'product')]
    private Collection $cartItems;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt ??= new \DateTimeImmutable();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(string $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getMaterial(): ?string
    {
        return $this->material;
    }

    public function setMaterial(string $material): static
    {
        $this->material = $material;

        return $this;
    }

    public function getQrTag(): ?QRTag
    {
        return $this->qrTag;
    }

    public function setQrTag(?QrTag $qrTag): static
    {
        if ($qrTag && $qrTag->getProduct() !== $this) {
            $qrTag->setProduct($this);
        }

        $this->qrTag = $qrTag;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(int $points): static
    {
        $this->points = $points;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
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

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setProduct($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getProduct() === $this) {
                $orderItem->setProduct(null);
            }
        }

        return $this;
    }

    public function getEcoInfo(): ?string
    {
        return $this->ecoInfo;
    }

    public function setEcoInfo(?string $ecoInfo): static
    {
        $this->ecoInfo = $ecoInfo;

        return $this;
    }

    /**
     * @return Collection<int, Stock>
     */
    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks->add($stock);
            $stock->setProduct($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) {
            // set the owning side to null (unless already changed)
            if ($stock->getProduct() === $this) {
                $stock->setProduct(null);
            }
        }

        return $this;
    }

    public function getTotalStockQuantity(): int
    {
        $total = 0;

        foreach ($this->stocks as $stock) {
            $total += $stock->getQuantity();
        }

        return $total;
    }

    public function setTotalStockQuantity(int $newTotal): void
    {
        // 1 Get the current total stock of this product
        $currentTotal = array_sum(
            array_map(fn($s) => $s->getQuantity(), $this->getStocks()->toArray())
        );

        // 2 Calculate the difference between desired total and current total
        $difference = $newTotal - $currentTotal;

        // 3 If the new total is lower, we need to deduct stock
        if ($difference < 0) {
            $this->deductStockQuantity(abs($difference));

            // 4 If the new total is higher, we can add stock (to be implemented)
        } elseif ($difference > 0) {
            // Add logic to increase stock (e.g., add to last stock record)
        }
    }

    public function deductStockQuantity(int $quantityToDeduct): void
    {
        $remainingToDeduct = $quantityToDeduct;

        //sort stocks oldest first by ID
        $stocks = $this->getStocks()->toArray();
        usort($stocks, fn($a, $b) => $a->getId() <=> $b->getId());

        foreach ($stocks as $stock) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $available = $stock->getQuantity();
            if ($available > 0) {
                $deduct = min($available, $remainingToDeduct);
                $stock->setQuantity($available - $deduct);
                $remainingToDeduct -= $deduct;
            }
        }

        if ($remainingToDeduct > 0) {
            throw new \Exception('Not enough stock available to fulfill deduction.');
        }
    }

    public function getStockStatus(): string
    {
        if ($this->getTotalStockQuantity() > 50) { // Example threshold for "In Stock"
            return 'In Stock';
        } elseif ($this->getTotalStockQuantity() >= 1 && $this->getTotalStockQuantity() < 50) { // Example threshold for "Low Stock"
            return 'Low Stock';
        } else {
            return 'Out of Stock';
        }
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItem $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setProduct($this);
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) {
            // set the owning side to null (unless already changed)
            if ($cartItem->getProduct() === $this) {
                $cartItem->setProduct(null);
            }
        }

        return $this;
    }

    public function getProfitPercentage(): float
    {
        //prevent division by zero
        if ($this->cost <= 0) {
            return 0.0;
        }

        $profit = $this->price - $this->cost;
        $profitPercentage = ($profit / $this->cost) * 100;

        //round to 2 decimal places
        return round($profitPercentage, 2);
    }
}
