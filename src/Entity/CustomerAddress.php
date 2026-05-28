<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CustomerAddressRepository;
use App\State\CustomerAddressProcessor;
use App\State\CustomerAddressesProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CustomerAddressRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/me/addresses',
            provider: CustomerAddressesProvider::class,
            normalizationContext: ['groups' => ['address:read']],
        ),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUser() == user"),
        new Post(
            processor: CustomerAddressProcessor::class,
            denormalizationContext: ['groups' => ['address:write']],
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUser() == user",
            processor: CustomerAddressProcessor::class,
            denormalizationContext: ['groups' => ['address:write']],
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUser() == user",
            processor: CustomerAddressProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['address:read']],
)]
class CustomerAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['address:read', 'order:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Customer $customer = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $label = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $recipientFirstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $recipientLastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $contactNumber = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $state = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $country = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['address:read', 'address:write'])]
    private ?string $courierNote = null;

    #[ORM\Column]
    #[Groups(['address:read', 'address:write', 'order:read'])]
    private bool $isDefault = false;

    #[ORM\Column]
    #[Groups(['address:read', 'address:write'])]
    private bool $hasPinpoint = false;

    #[ORM\Column]
    #[Groups(['address:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['address:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getRecipientFirstName(): ?string
    {
        return $this->recipientFirstName;
    }

    public function setRecipientFirstName(?string $recipientFirstName): static
    {
        $this->recipientFirstName = $recipientFirstName;

        return $this;
    }

    public function getRecipientLastName(): ?string
    {
        return $this->recipientLastName;
    }

    public function setRecipientLastName(?string $recipientLastName): static
    {
        $this->recipientLastName = $recipientLastName;

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

    public function setAddress(string $address): static
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

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): static
    {
        $this->state = $state;

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

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCourierNote(): ?string
    {
        return $this->courierNote;
    }

    public function setCourierNote(?string $courierNote): static
    {
        $this->courierNote = $courierNote;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function hasPinpoint(): bool
    {
        return $this->hasPinpoint;
    }

    public function setHasPinpoint(bool $hasPinpoint): static
    {
        $this->hasPinpoint = $hasPinpoint;

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

    #[Groups(['address:read', 'order:read'])]
    public function getRecipientFullName(): string
    {
        $parts = array_filter([
            $this->recipientFirstName,
            $this->recipientLastName,
        ]);

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        $customer = $this->customer;
        if ($customer) {
            return trim(sprintf('%s %s', $customer->getFirstName() ?? '', $customer->getLastName() ?? ''));
        }

        return 'Customer';
    }

    #[Groups(['address:read', 'order:read'])]
    public function getFormattedAddress(): string
    {
        return implode(', ', array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country,
        ]));
    }
}
