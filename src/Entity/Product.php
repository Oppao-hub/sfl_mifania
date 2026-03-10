<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Enum\Size;
use App\Entity\Enum\Color;
use App\Entity\Enum\Gender;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups; // REQUIRED FOR API

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['product:read']]
)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Product name cannot be empty.")]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    #[Groups(['product:read'])]
    private ?string $name = null;

    #[Assert\NotBlank(message: "Material cannot be empty.")]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    #[Groups(['product:read'])]
    private ?string $material = null;

    #[ORM\Column(length: 20, enumType: Size::class)]
    #[Groups(['product:read'])]
    private ?Size $size = null;

    #[ORM\Column(length: 50, nullable: false, enumType: Color::class)]
    #[Groups(['product:read'])]
    private ?Color $color = null;

    #[ORM\Column(length: 20, enumType: Gender::class, nullable: false)]
    #[Groups(['product:read'])]
    private ?Gender $gender = null;

    #[Assert\PositiveOrZero(message: "Price must be positive or zero.")]
    #[Assert\Type(type: 'numeric', message: "Price must be a number.")]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['product:read'])]
    private ?string $price = null;

    #[Assert\PositiveOrZero(message: "Cost must be positive or zero.")]
    #[Assert\Type(type: 'numeric', message: "Cost must be a number.")]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $cost = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['product:read'])]
    private ?string $slug = null;

    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?QRTag $qrTag = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['product:read'])]
    private ?string $image = 'default.png';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
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

    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product:read'])]
    private ?SubCategory $subCategory = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[Groups(['product:read'])]
    private ?Category $category = null;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
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

    #[ORM\PrePersist]
    public function generateSlugOnPersist(): void
    {
        if (empty($this->slug) && !empty($this->name)) {
            $this->slug = $this->generateSlug($this->name);
        }
    }

    private function generateSlug(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        $text = strtolower($text);
        return $text;
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getPrice(): ?string { return $this->price; }
    public function setPrice(string $price): static { $this->price = $price; return $this; }
    public function getCost(): ?string { return $this->cost; }
    public function setCost(string $cost): static { $this->cost = $cost; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getSize(): ?Size { return $this->size; }
    public function setSize(Size $size): static { $this->size = $size; return $this; }
    public function getColor(): ?Color { return $this->color; }
    public function setColor(?Color $color): static { $this->color = $color; return $this; }
    public function getGender(): ?Gender { return $this->gender; }
    public function setGender(?Gender $gender): static { $this->gender = $gender; return $this; }
    public function getMaterial(): ?string { return $this->material; }
    public function setMaterial(string $material): static { $this->material = $material; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getQrTag(): ?QRTag { return $this->qrTag; }
    public function setQrTag(?QRTag $qrTag): static
    {
        if ($this->qrTag !== null && $this->qrTag->getProduct() === $this) { $this->qrTag->setProduct(null); }
        if ($qrTag !== null) { $qrTag->setProduct($this); }
        $this->qrTag = $qrTag;
        return $this;
    }
    public function getImage(): ?string { return $this->image; }
    public function setImage(string $image): self { $this->image = $image; return $this; }
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
    public function getSubCategory(): ?SubCategory { return $this->subCategory; }
    public function setSubCategory(?SubCategory $subCategory): static { $this->subCategory = $subCategory; return $this; }
    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }
}
