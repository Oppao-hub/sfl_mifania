<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SubCategoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    normalizationContext: ['groups' => ['subcategory:read']],
    denormalizationContext: ['groups' => ['subcategory:write']]
)]
#[ORM\Entity(repositoryClass: SubCategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'This URL slug is already in use. Please choose another.')]
class SubCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subcategory:read', 'category:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Groups(['subcategory:read', 'subcategory:write', 'category:read'])]
    #[Assert\NotBlank(message: 'Please enter a name for this sub-category.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'The name must be at least {{ limit }} characters long.',
        maxMessage: 'The name cannot be longer than {{ limit }} characters.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['subcategory:read', 'subcategory:write'])]
    #[Assert\NotBlank(message: 'A brief description is required to provide context.')]
    #[Assert\Length(max: 500, maxMessage: 'The description cannot exceed {{ limit }} characters.')]
    private ?string $description = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Groups(['subcategory:read', 'subcategory:write', 'category:read'])]
    #[Assert\Regex(
        pattern: '/^[a-z0-9\-]+$/',
        message: 'The slug can only contain lowercase letters, numbers, and hyphens (e.g., maxi-dresses).'
    )]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'subCategories')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['subcategory:read', 'subcategory:write'])]
    #[Assert\NotNull(message: 'You must assign this to a parent category.')]
    private ?Category $category = null;

    #[ORM\Column]
    #[Groups(['subcategory:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Groups(['subcategory:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'subCategory')]
    #[Groups(['subcategory:read'])]
    private Collection $products;

    // --- LIFECYCLE CALLBACKS ---

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ADDED: Automatic Slug Generator
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSlugValue(): void
    {
        if (empty($this->slug) && !empty($this->name)) {
            $text = preg_replace('~[^\pL\d]+~u', '-', $this->name);
            $text = trim($text, '-');
            $this->slug = strtolower($text);
        }
    }

    // --- GETTERS & SETTERS ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
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

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
