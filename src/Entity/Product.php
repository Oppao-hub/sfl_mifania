<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\Enum\Size;
use App\Entity\Enum\Color;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'This product slug is already in use. Please choose a different name.')]
#[ApiResource(
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']]
)]
#[ApiFilter(SearchFilter::class, properties: ['color' => 'exact'])]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Product name cannot be empty.')]
    #[Assert\Length(max: 100, maxMessage: 'Product name cannot exceed 100 characters.')]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Material cannot be empty.')]
    #[Assert\Length(max: 100, maxMessage: 'Material cannot exceed 100 characters.')]
    #[Groups(['product:read'])]
    private ?string $material = null;

    #[ORM\Column(length: 20, enumType: Size::class)]
    #[Assert\NotNull(message: 'Please select a size.')]
    #[Groups(['product:read'])]
    private ?Size $size = null;

    #[ORM\Column(length: 50, nullable: false, enumType: Color::class)]
    #[Assert\NotNull(message: 'Please select a color palette.')]
    #[Groups(['product:read'])]
    private ?Color $color = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Price is required.')]
    #[Assert\PositiveOrZero(message: 'Price must be positive or zero.')]
    #[Assert\Type(type: 'numeric', message: 'Price must be a valid number.')]
    #[Groups(['product:read'])]
    private ?string $price = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Archival cost is required.')]
    #[Assert\PositiveOrZero(message: 'Cost must be positive or zero.')]
    #[Assert\Type(type: 'numeric', message: 'Cost must be a valid number.')]
    private ?string $cost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'Description cannot exceed 2000 characters.')]
    #[Groups(['product:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['product:read'])]
    private ?string $slug = null;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    #[Groups(['product:read'])]
    private ?QRTag $qrTag = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $image = 'default.png';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Quick Impact Stats should be short and concise.')]
    #[Groups(['product:read'])]
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

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'You must assign this product to a sub-category.')]
    #[Groups(['product:read'])]
    private ?SubCategory $subCategory = null;

    /**
     * @var Collection<int, Customer>
     */
    #[ORM\ManyToMany(targetEntity: Customer::class, mappedBy: 'wishlist')]
    private Collection $wishlisted;

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Story $story = null;

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[Groups(['product:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
        $this->wishlisted = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setInitialTimestamps(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt ??= new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // BUG FIX: Actually set the slug property!
    #[ORM\PrePersist]
    public function generateSlugOnPersist(): void
    {
        if (empty($this->slug) && !empty($this->name)) {
            $text = preg_replace('~[^\pL\d]+~u', '-', $this->name);
            $text = trim($text, '-');
            $this->slug = strtolower($text);
        }
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }

    // BUG FIX: ? Added to prevent 500 error
    public function setName(?string $name): static { $this->name = $name; return $this; }

    public function getPrice(): ?string { return $this->price; }

    public function setPrice(?string $price): static { $this->price = $price; return $this; }

    public function getCost(): ?string { return $this->cost; }

    public function setCost(?string $cost): static { $this->cost = $cost; return $this; }

    public function getDescription(): ?string { return $this->description; }

    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getSize(): ?Size { return $this->size; }

    // BUG FIX: ? Added to prevent 500 error
    public function setSize(?Size $size): static { $this->size = $size; return $this; }

    public function getColor(): ?Color { return $this->color; }

    public function setColor(?Color $color): static { $this->color = $color; return $this; }

    public function getMaterial(): ?string { return $this->material; }

    // BUG FIX: ? Added to prevent 500 error
    public function setMaterial(?string $material): static { $this->material = $material; return $this; }

    public function getSlug(): ?string { return $this->slug; }

    // BUG FIX: ? Added to prevent 500 error
    public function setSlug(?string $slug): static { $this->slug = $slug; return $this; }

    public function getQrTag(): ?QRTag { return $this->qrTag; }

    public function setQrTag(?QRTag $qrTag): static
    {
        if ($this->qrTag !== null && $this->qrTag->getProduct() === $this) { $this->qrTag->setProduct(null); }
        if ($qrTag !== null) { $qrTag->setProduct($this); }
        $this->qrTag = $qrTag;
        return $this;
    }

    public function getImage(): ?string { return $this->image; }

    // BUG FIX: ? Added to prevent 500 error
    public function setImage(?string $image): self { $this->image = $image ?: 'default.png'; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getOrderItems(): Collection { return $this->orderItems; }
    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) { $this->orderItems->add($orderItem); $orderItem->setProduct($this); }
        return $this;
    }
    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) { if ($orderItem->getProduct() === $this) { $orderItem->setProduct(null); } }
        return $this;
    }

    public function getEcoInfo(): ?string { return $this->ecoInfo; }
    public function setEcoInfo(?string $ecoInfo): static { $this->ecoInfo = $ecoInfo; return $this; }

    public function getStocks(): Collection { return $this->stocks; }
    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) { $this->stocks->add($stock); $stock->setProduct($this); }
        return $this;
    }
    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) { if ($stock->getProduct() === $this) { $stock->setProduct(null); } }
        return $this;
    }

    public function getTotalStockQuantity(): int
    {
        $total = 0;
        foreach ($this->stocks as $stock) { $total += $stock->getQuantity(); }
        return $total;
    }

    public function setTotalStockQuantity(int $newTotal): void
    {
        $currentTotal = array_sum(array_map(fn($s) => $s->getQuantity(), $this->getStocks()->toArray()));
        $difference = $newTotal - $currentTotal;
        if ($difference < 0) { $this->deductStockQuantity(abs($difference)); } elseif ($difference > 0) { $this->increaseStockQuantity($difference); }
    }

    public function increaseStockQuantity(int $quantityToAdd): void
    {
        $stocks = $this->getStocks()->toArray();
        usort($stocks, fn($a, $b) => $b->getId() <=> $a->getId());
        if (!empty($stocks)) { $latestStock = $stocks[0]; $latestStock->setQuantity($latestStock->getQuantity() + $quantityToAdd); }
        else { throw new \Exception('No stock record found.'); }
    }

    public function deductStockQuantity(int $quantityToDeduct): void
    {
        $remainingToDeduct = $quantityToDeduct;
        $stocks = $this->getStocks()->toArray();
        usort($stocks, fn($a, $b) => $a->getId() <=> $b->getId());
        foreach ($stocks as $stock) {
            if ($remainingToDeduct <= 0) break;
            $available = $stock->getQuantity();
            if ($available > 0) { $deduct = min($available, $remainingToDeduct); $stock->setQuantity($available - $deduct); $remainingToDeduct -= $deduct; }
        }
        if ($remainingToDeduct > 0) throw new \Exception('Not enough stock.');
    }

    public function getStockStatus(): string
    {
        $quantity = $this->getTotalStockQuantity();
        if ($quantity > 50) return 'In Stock';
        if ($quantity >= 1) return 'Low Stock';
        return 'Out of Stock';
    }

    public function getCartItems(): Collection { return $this->cartItems; }
    public function addCartItem(CartItem $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) { $this->cartItems->add($cartItem); $cartItem->setProduct($this); }
        return $this;
    }
    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) { if ($cartItem->getProduct() === $this) { $cartItem->setProduct(null); } }
        return $this;
    }

    public function getProfitPercentage(): float
    {
        $cost = floatval($this->cost); $price = floatval($this->price);
        if ($cost <= 0) return 0.0;
        return round((($price - $cost) / $cost) * 100, 2);
    }

    public function getSubCategory(): ?SubCategory
    {
        return $this->subCategory;
    }

    // BUG FIX: ? Added to prevent 500 error
    public function setSubCategory(?SubCategory $subCategory): static
    {
        $this->subCategory = $subCategory; return $this;
    }

    /**
     * @return Collection<int, Customer>
     */
    public function getWishlisted(): Collection
    {
        return $this->wishlisted;
    }
    public function addWishlisted(Customer $wishlisted): static
    {
        if (!$this->wishlisted->contains($wishlisted)) {
            $this->wishlisted->add($wishlisted);
            $wishlisted->addWishlist($this);
        }
        return $this;
    }
    public function removeWishlisted(Customer $wishlisted): static
    {
        if ($this->wishlisted->removeElement($wishlisted)) {
            $wishlisted->removeWishlist($this);
        }
        return $this;
    }

    public function getStory(): ?Story { return $this->story; }
    public function setStory(?Story $story): static { $this->story = $story; return $this; }
}
