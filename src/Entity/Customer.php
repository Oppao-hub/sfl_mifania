<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN') or object.getUser() == user"),
        new Patch(security: "is_granted('ROLE_ADMIN') or object.getUser() == user"),
    ],
    normalizationContext: ['groups' => ['customer:read']],
    denormalizationContext: ['groups' => ['customer:write']]
)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['customer:read', 'user:read'])] // 💡 ADDED user:read
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'-]+$/u",
        message: 'Your first name can only contain letters, spaces, hyphens, and apostrophes.'
    )]
    #[Groups(['customer:read', 'customer:write', 'order:read', 'user:read'])] // 💡 ADDED user:read
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'-]+$/u",
        message: 'Your last name can only contain letters, spaces, hyphens, and apostrophes.'
    )]
    #[Groups(['customer:read', 'customer:write', 'order:read', 'user:read'])] // 💡 ADDED user:read
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^(\+63|09)\d{9}$/',
        message: 'The phone number must start with "+63" or "09" and be followed by exactly 9 digits (e.g., +639171234567 or 09171234567).'
    )]
    #[Groups(['customer:read', 'customer:write', 'user:read'])] // 💡 ADDED user:read
    private ?string $contactNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank]
    #[Groups(['customer:read', 'customer:write', 'user:read'])] // 💡 ADDED user:read
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'-]+$/u",
        message: 'The city name can only contain letters, spaces, hyphens, and apostrophes.'
    )]
    #[Groups(['customer:read', 'customer:write', 'user:read'])] // 💡 ADDED user:read
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'-]+$/u",
        message: 'The country name can only contain letters, spaces, hyphens, and apostrophes.'
    )]
    #[Groups(['customer:read', 'customer:write', 'user:read'])] // 💡 ADDED user:read
    private ?string $country = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'-]+$/u",
        message: 'The state name can only contain letters, spaces, hyphens, and apostrophes.'
    )]
    #[Groups(['customer:read', 'customer:write', 'user:read'])] // 💡 ADDED user:read
    private ?string $state = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 20,
        maxMessage: 'The postal code cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['customer:read', 'customer:write', 'user:read'])] // 💡 ADDED user:read
    private ?string $postalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png'],
        mimeTypesMessage: 'Please upload a valid JPEG or PNG image.'
    )]
    #[Groups(['customer:read', 'user:read', 'customer:write'])] // 💡 ADDED user:read
    private ?string $avatar = null;

    #[ORM\Column]
    #[Groups(['customer:read'])]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column]
    #[Groups(['customer:read'])]
    private ?\DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer')]
    private Collection $orders;

    /**
     * @var Collection<int, RewardTransaction>
     */
    #[ORM\OneToMany(targetEntity: RewardTransaction::class, mappedBy: 'customer')]
    private Collection $rewardTransactions;

    #[ORM\OneToOne(mappedBy: 'customer', cascade: ['persist', 'remove'])]
    #[Groups(['customer:read'])]
    private ?Wallet $wallet = null;

    /**
     * @var Collection<int, Cart>
     */
    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'customer', cascade: ['persist', 'remove'])]
    private Collection $carts;

    /**
     * @var Collection<int, Redemption>
     */
    #[ORM\OneToMany(targetEntity: Redemption::class, mappedBy: 'customer')]
    private Collection $redemptions;

    #[ORM\OneToOne(inversedBy: 'customer', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups(['customer:read', 'customer:write'])]
    #[Assert\Valid]
    private ?User $user = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'wishlisted')]
    private Collection $wishlist;

    /**
     * @var Collection<int, CustomerAddress>
     */
    #[ORM\OneToMany(targetEntity: CustomerAddress::class, mappedBy: 'customer', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $addresses;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->orders = new ArrayCollection();
        $this->rewardTransactions = new ArrayCollection();
        $this->carts = new ArrayCollection();
        $this->redemptions = new ArrayCollection();
        $this->wishlist = new ArrayCollection();
        $this->addresses = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getContactNumber(): ?string
    {
        return $this->contactNumber;
    }

    public function setContactNumber(?string $contactNumber): static
    {
        $this->contactNumber = $contactNumber;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    #[Groups(['customer:read'])]
    #[SerializedName('avatarUrl')]
    public function getAvatarUrl(): ?string {
        return $this->avatar ? '/uploads/customer/avatars/' . $this->avatar : null;
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

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCustomer($this);
        }
        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getCustomer() === $this) {
                $order->setCustomer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, RewardTransaction>
     */
    public function getRewardTransactions(): Collection
    {
        return $this->rewardTransactions;
    }

    public function addRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if (!$this->rewardTransactions->contains($rewardTransaction)) {
            $this->rewardTransactions->add($rewardTransaction);
            $rewardTransaction->setCustomer($this);
        }
        return $this;
    }

    public function removeRewardTransaction(RewardTransaction $rewardTransaction): static
    {
        if ($this->rewardTransactions->removeElement($rewardTransaction)) {
            if ($rewardTransaction->getCustomer() === $this) {
                $rewardTransaction->setCustomer(null);
            }
        }
        return $this;
    }

    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }

    public function setWallet(?Wallet $wallet): static
    {
        if ($wallet === null && $this->wallet !== null) {
            $this->wallet->setCustomer(null);
        }
        if ($wallet !== null && $wallet->getCustomer() !== $this) {
            $wallet->setCustomer($this);
        }
        $this->wallet = $wallet;
        return $this;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        return $this->carts;
    }

    public function addCart(Cart $cart): static
    {
        if (!$this->carts->contains($cart)) {
            $this->carts->add($cart);
            $cart->setCustomer($this);
        }
        return $this;
    }

    public function removeCart(Cart $cart): static
    {
        if ($this->carts->removeElement($cart)) {
            if ($cart->getCustomer() === $this) {
                $cart->setCustomer(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Redemption>
     */
    public function getRedemptions(): Collection
    {
        return $this->redemptions;
    }

    public function addRedemption(Redemption $redemption): static
    {
        if (!$this->redemptions->contains($redemption)) {
            $this->redemptions->add($redemption);
            $redemption->setCustomer($this);
        }
        return $this;
    }

    public function removeRedemption(Redemption $redemption): static
    {
        if ($this->redemptions->removeElement($redemption)) {
            if ($redemption->getCustomer() === $this) {
                $redemption->setCustomer(null);
            }
        }
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getWishlist(): Collection
    {
        return $this->wishlist;
    }

    public function addWishlist(Product $wishlist): static
    {
        if (!$this->wishlist->contains($wishlist)) {
            $this->wishlist->add($wishlist);
        }

        return $this;
    }

    public function removeWishlist(Product $wishlist): static
    {
        $this->wishlist->removeElement($wishlist);

        return $this;
    }

    /**
     * @return Collection<int, CustomerAddress>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(CustomerAddress $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setCustomer($this);
        }

        return $this;
    }

    public function removeAddress(CustomerAddress $address): static
    {
        if ($this->addresses->removeElement($address)) {
            if ($address->getCustomer() === $this) {
                $address->setCustomer(null);
            }
        }

        return $this;
    }
}
