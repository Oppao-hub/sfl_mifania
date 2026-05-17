<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\RewardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RewardRepository::class)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    normalizationContext: ['groups' => ['reward:read']]
)]
class Reward
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['reward:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['reward:read'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['reward:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['reward:read'])]
    private ?int $pointsRequired = null;

    #[ORM\Column]
    #[Groups(['reward:read'])]
    private ?bool $isActive = null;

    #[ORM\Column]
    #[Groups(['reward:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Redemption>
     */
    #[ORM\OneToMany(targetEntity: Redemption::class, mappedBy: 'reward')]
    private Collection $redemptions;

    public function __construct()
    {
        $this->redemptions = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPointsRequired(): ?int
    {
        return $this->pointsRequired;
    }

    public function setPointsRequired(int $pointsRequired): static
    {
        $this->pointsRequired = $pointsRequired;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
            $redemption->setReward($this);
        }

        return $this;
    }

    public function removeRedemption(Redemption $redemption): static
    {
        if ($this->redemptions->removeElement($redemption)) {
            // set the owning side to null (unless already changed)
            if ($redemption->getReward() === $this) {
                $redemption->setReward(null);
            }
        }

        return $this;
    }
}
