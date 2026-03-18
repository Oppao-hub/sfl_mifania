<?php

namespace App\Entity;

use App\Repository\StoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StoryRepository::class)]
class Story
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $materialContent = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $artisanContent = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $dyeingContent = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'story')]
    private Collection $product;

    public function __construct()
    {
        $this->product = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getMaterialContent(): ?string
    {
        return $this->materialContent;
    }

    public function setMaterialContent(string $materialContent): static
    {
        $this->materialContent = $materialContent;

        return $this;
    }

    public function getArtisanContent(): ?string
    {
        return $this->artisanContent;
    }

    public function setArtisanContent(string $artisanContent): static
    {
        $this->artisanContent = $artisanContent;

        return $this;
    }

    public function getDyeingContent(): ?string
    {
        return $this->dyeingContent;
    }

    public function setDyeingContent(string $dyeingContent): static
    {
        $this->dyeingContent = $dyeingContent;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProduct(): Collection
    {
        return $this->product;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->product->contains($product)) {
            $this->product->add($product);
            $product->setStory($this);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->product->removeElement($product)) {
            // set the owning side to null (unless already changed)
            if ($product->getStory() === $this) {
                $product->setStory(null);
            }
        }

        return $this;
    }
}
