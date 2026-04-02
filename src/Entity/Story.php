<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\StoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['story:read']],
    denormalizationContext: ['groups' => ['story:write']]
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: StoryRepository::class)]
#[UniqueEntity(fields: ['title'], message: 'A narrative story with this title already exists.')]
class Story
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['story:read', 'product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['story:read', 'story:write', 'product:read'])]
    #[Assert\NotBlank(message: 'The story headline cannot be empty.')]
    #[Assert\Length(max: 100, maxMessage: 'The headline cannot be longer than {{ limit }} characters.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['story:read', 'story:write', 'product:read'])]
    #[Assert\NotBlank(message: 'Please describe the raw materials origin.')]
    private ?string $materialContent = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['story:read', 'story:write', 'product:read'])]
    #[Assert\NotBlank(message: 'Please describe the ethical craft and artisans.')]
    private ?string $artisanContent = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['story:read', 'story:write', 'product:read'])]
    #[Assert\NotBlank(message: 'Please describe the eco-dyeing process.')]
    private ?string $dyeingContent = null;

    #[ORM\Column]
    #[Groups(['story:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['story:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Product>
     */
    // FIX: Renamed to $products (plural) and mapped securely
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'story')]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }

    public function getMaterialContent(): ?string { return $this->materialContent; }
    public function setMaterialContent(?string $materialContent): static { $this->materialContent = $materialContent; return $this; }

    public function getArtisanContent(): ?string { return $this->artisanContent; }
    public function setArtisanContent(?string $artisanContent): static { $this->artisanContent = $artisanContent; return $this; }

    public function getDyeingContent(): ?string { return $this->dyeingContent; }
    public function setDyeingContent(?string $dyeingContent): static { $this->dyeingContent = $dyeingContent; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection { return $this->products; }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setStory($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getStory() === $this) {
                $product->setStory(null);
            }
        }
        return $this;
    }
}
