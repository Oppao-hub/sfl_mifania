<?php

namespace App\Entity;

use App\Entity\Enum\Size;
use App\Entity\Enum\Color;
use App\Entity\Enum\Gender;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    // 1. Limit what data is sent to the frontend
    normalizationContext: ['groups' => ['product:read']],
    // 2. Setup pagination to match your UI (1-12 items)
    paginationItemsPerPage: 12
)]
// 3. Add filters for your sidebar
#[ApiFilter(SearchFilter::class, properties: [
    'slug' => 'exact',
    'category' => 'exact',
    'subCategory' => 'exact',
    'color' => 'exact',
    'size' => 'exact',
    'gender' => 'exact'
])]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(OrderFilter::class, properties: ['price', 'createdAt'], arguments: ['orderParameterName' => 'sort'])]
class Product
{
    #[Groups(['product:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['product:read'])]
    #[Assert\NotBlank(message: "Product name cannot be empty.")]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[Groups(['product:read'])]
    #[Assert\NotBlank(message: "Material cannot be empty.")]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100)]
    private ?string $material = null;

    #[Groups(['product:read'])]
    #[ORM\Column(length: 20, enumType: Size::class)]
    private ?Size $size = null;

    #[Groups(['product:read'])]
    #[ORM\Column(length: 50, nullable: false, enumType: Color::class)]
    private ?Color $color = null;

    #[Groups(['product:read'])]
    #[ORM\Column(length: 20, enumType: Gender::class, nullable: false)]
    private ?Gender $gender = null;

    #[Groups(['product:read'])]
    #[Assert\PositiveOrZero(message: "Price must be positive or zero.")]
    #[Assert\Type(type: 'numeric', message: "Price must be a number.")]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $price = null;

    #[Groups(['product:read'])]
    #[Assert\PositiveOrZero(message: "Cost must be positive or zero.")]
    #[Assert\Type(type: 'numeric', message: "Cost must be a number.")]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $cost = null;

    #[Groups(['product:read'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[Groups(['product:read'])]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[Groups(['product:read'])]
    #[ORM\OneToOne(mappedBy: 'product', cascade: ['persist', 'remove'])]
    private ?QRTag $qrTag = null;

    #[Groups(['product:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = 'default.png';

    #[Groups(['product:read'])]
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

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SubCategory $subCategory = null;

    #[Groups(['product:read'])]
    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Category $category = null;
    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
    }

    /**
     * Sets createdAt and updatedAt automatically on pre-persist.
     */
    #[ORM\PrePersist]
    public function setInitialTimestamps(): void
    {
        // Only runs if null, ensuring existing dates are not overwritten
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt ??= new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdateTimestamp(): void
    {
        // Always updates the timestamp on entity modification
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Ensures the slug is set before persisting.
     */
    #[ORM\PrePersist]
    public function generateSlugOnPersist(): void
    {
        if (empty($this->slug) && !empty($this->name)) {
            $this->slug = $this->generateSlug($this->name);
        }
    }

    /**
     * Basic slug generation helper. In a real app, use the Symfony Slugger component.
     */
    private function generateSlug(string $text): string
    {
        // Replace non-alphanumeric characters with hyphens
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Trim leading/trailing hyphens
        $text = trim($text, '-');
        // Convert to lowercase
        $text = strtolower($text);

        return $text;
    }

    // --- Accessors (Getters and Setters) ---

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSize(): ?Size
    {
        return $this->size;
    }

    public function setSize(Size $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getColor(): ?Color
    {
        return $this->color;
    }

    public function setColor(?Color $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): static
    {
        $this->gender = $gender;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getQrTag(): ?QRTag
    {
        return $this->qrTag;
    }

    public function setQrTag(?QrTag $qrTag): static
    {
        // Unset the current QRTag relationship if it exists
        if ($this->qrTag !== null && $this->qrTag->getProduct() === $this) {
            $this->qrTag->setProduct(null);
        }

        // Set the new QRTag relationship and ensure bidirectional link
        if ($qrTag !== null) {
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

    public function getCreatedAt(): ?\DateTimeImmutable
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

    /**
     * This method is intended for simple stock adjustment, primarily for deduction.
     * For addition, creating a new Stock entity (e.g., via a service layer) is the better practice
     * to accurately track inventory inflow.
     */
    public function setTotalStockQuantity(int $newTotal): void
    {
        // 1 Get the current total stock of this product
        $currentTotal = array_sum(
            array_map(fn($s) => $s->getQuantity(), $this->getStocks()->toArray())
        );

        // 2 Calculate the difference between desired total and current total
        $difference = $newTotal - $currentTotal;

        // 3 If the new total is lower, we need to deduct stock (FIFO)
        if ($difference < 0) {
            $this->deductStockQuantity(abs($difference));

            // 4 If the new total is higher, we increase stock (LIFO for modification)
        } elseif ($difference > 0) {
            $this->increaseStockQuantity($difference);
        }
    }

    public function increaseStockQuantity(int $quantityToAdd): void
    {
        // Sort stocks by ID in descending order to get the latest (LIFO for modification)
        $stocks = $this->getStocks()->toArray();
        usort($stocks, fn($a, $b) => $b->getId() <=> $a->getId());

        // If stocks exist, add the quantity to the latest stock record
        if (!empty($stocks)) {
            $latestStock = $stocks[0];
            $latestStock->setQuantity($latestStock->getQuantity() + $quantityToAdd);
        } else {
            // If no stock record exists, throw an exception as a new Stock entity should be created
            throw new \Exception('Cannot increase stock: No existing stock record found to modify. A new Stock entity must be created via a service.');
        }
    }

    public function deductStockQuantity(int $quantityToDeduct): void
    {
        $remainingToDeduct = $quantityToDeduct;

        // Sort stocks oldest first by ID (FIFO for deduction)
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
        $quantity = $this->getTotalStockQuantity();
        if ($quantity > 50) { // Example threshold for "In Stock"
            return 'In Stock';
        } elseif ($quantity >= 1 && $quantity <= 50) { // Example threshold for "Low Stock"
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
        // Use floatval for accurate comparison and calculation
        $cost = floatval($this->cost);
        $price = floatval($this->price);

        // Prevent division by zero or negative cost
        if ($cost <= 0) {
            return 0.0;
        }

        $profit = $price - $cost;
        $profitPercentage = ($profit / $cost) * 100;

        // Round to 2 decimal places
        return round($profitPercentage, 2);
    }

    public function getSubCategory(): ?SubCategory
    {
        return $this->subCategory;
    }

    public function setSubCategory(?SubCategory $subCategory): static
    {
        $this->subCategory = $subCategory;

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
}
