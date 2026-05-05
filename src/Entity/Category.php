<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['category:read']]
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'This URL slug is already in use. Please choose another.')]
#[UniqueEntity(fields: ['name'], message: 'A category with this name already exists.')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['category:read', 'subcategory:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['category:read', 'subcategory:read'])]
    #[Assert\NotBlank(message: 'Please enter a name for this category.')]
    #[Assert\Length(max: 50, maxMessage: 'The name cannot be longer than {{ limit }} characters.')]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['category:read'])]
    #[Assert\NotBlank(message: 'A brief description is required.')]
    private ?string $description = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Groups(['category:read'])]
    // Removed NotBlank here so the auto-generator can do its job if left empty!
    #[Assert\Regex(
        pattern: '/^[a-z0-9\-]+$/',
        message: 'The slug can only contain lowercase letters, numbers, and hyphens.'
    )]
    private ?string $slug = null;

    #[ORM\Column]
    #[Groups(['category:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['category:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, SubCategory>
     */
    #[ORM\OneToMany(targetEntity: SubCategory::class, mappedBy: 'category')]
    #[Groups(['category:read'])]
    private Collection $subCategories;

    public function __construct()
    {
        $this->subCategories = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->createdAt ??= new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ADDED HOOKS SO IT RUNS AUTOMATICALLY
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
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
     * @return Collection<int, SubCategory>
     */
    public function getSubCategories(): Collection
    {
        return $this->subCategories;
    }

    public function addSubCategory(SubCategory $subCategory): static
    {
        if (!$this->subCategories->contains($subCategory)) {
            $this->subCategories->add($subCategory);
            $subCategory->setCategory($this);
        }
        return $this;
    }

    public function removeSubCategory(SubCategory $subCategory): static
    {
        if ($this->subCategories->removeElement($subCategory)) {
            if ($subCategory->getCategory() === $this) {
                $subCategory->setCategory(null);
            }
        }
        return $this;
    }
}
