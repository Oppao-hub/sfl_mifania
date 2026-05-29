<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\CustomerPaymentMethodRepository;
use App\State\CustomerPaymentMethodProcessor;
use App\State\CustomerPaymentMethodsProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CustomerPaymentMethodRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/saved-payment-methods',
            security: "is_granted('ROLE_USER')",
            provider: CustomerPaymentMethodsProvider::class,
            paginationEnabled: false,
            normalizationContext: ['groups' => ['payment_method:read']],
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUser() == user"),
        new Post(
            security: "is_granted('ROLE_USER')",
            processor: CustomerPaymentMethodProcessor::class,
            denormalizationContext: ['groups' => ['payment_method:write']],
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUser() == user",
            processor: CustomerPaymentMethodProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['payment_method:read']],
)]
class CustomerPaymentMethod
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['payment_method:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paymentMethods')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    #[ORM\Column(length: 32)]
    #[Groups(['payment_method:read', 'payment_method:write'])]
    private ?string $providerType = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?string $cardBrand = null;

    #[ORM\Column(length: 4, nullable: true)]
    #[Groups(['payment_method:read'])]
    private ?string $lastFour = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['payment_method:read', 'payment_method:write'])]
    private ?int $expiryMonth = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['payment_method:read', 'payment_method:write'])]
    private ?int $expiryYear = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Groups(['payment_method:read', 'payment_method:write'])]
    private ?string $holderName = null;

    #[ORM\Column]
    #[Groups(['payment_method:read'])]
    private bool $isConnected = true;

    #[ORM\Column]
    #[Groups(['payment_method:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['payment_method:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /** Transient — only used on create; never persisted */
    #[Groups(['payment_method:write'])]
    private ?string $cardNumber = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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

    public function getProviderType(): ?string
    {
        return $this->providerType;
    }

    public function setProviderType(string $providerType): static
    {
        $this->providerType = $providerType;

        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(?string $cardBrand): static
    {
        $this->cardBrand = $cardBrand;

        return $this;
    }

    public function getLastFour(): ?string
    {
        return $this->lastFour;
    }

    public function setLastFour(?string $lastFour): static
    {
        $this->lastFour = $lastFour;

        return $this;
    }

    public function getExpiryMonth(): ?int
    {
        return $this->expiryMonth;
    }

    public function setExpiryMonth(?int $expiryMonth): static
    {
        $this->expiryMonth = $expiryMonth;

        return $this;
    }

    public function getExpiryYear(): ?int
    {
        return $this->expiryYear;
    }

    public function setExpiryYear(?int $expiryYear): static
    {
        $this->expiryYear = $expiryYear;

        return $this;
    }

    public function getHolderName(): ?string
    {
        return $this->holderName;
    }

    public function setHolderName(?string $holderName): static
    {
        $this->holderName = $holderName;

        return $this;
    }

    public function isConnected(): bool
    {
        return $this->isConnected;
    }

    public function setIsConnected(bool $isConnected): static
    {
        $this->isConnected = $isConnected;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(?string $cardNumber): static
    {
        $this->cardNumber = $cardNumber;

        return $this;
    }

    #[Groups(['payment_method:read'])]
    public function getDisplayName(): string
    {
        return match ($this->providerType) {
            'paypal' => 'PayPal',
            'google_pay' => 'Google Pay',
            'apple_pay' => 'Apple Pay',
            'card' => $this->formatCardBrandName($this->cardBrand ?? 'Card'),
            default => ucfirst(str_replace('_', ' ', (string) $this->providerType)),
        };
    }

    #[Groups(['payment_method:read'])]
    public function getMaskedNumber(): ?string
    {
        if ($this->providerType !== 'card' || !$this->lastFour) {
            return null;
        }

        return sprintf('.... .... .... %s', $this->lastFour);
    }

    private function formatCardBrandName(string $brand): string
    {
        return match (strtolower($brand)) {
            'visa' => 'Visa',
            'mastercard' => 'Mastercard',
            'amex', 'american_express' => 'American Express',
            'jcb' => 'JCB',
            default => ucfirst($brand),
        };
    }
}
