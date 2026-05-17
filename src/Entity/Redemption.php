<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\RedemptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RedemptionRepository::class)]
#[ApiResource(
    operations: [
        new Get(security: "is_granted('ROLE_ADMIN') or object.getCustomer().getUser() == user"),
        new GetCollection(security: "is_granted('ROLE_ADMIN') or user.getCustomer() != null"),
        new Post(processor: \App\State\RedemptionProcessor::class, security: "is_granted('ROLE_CUSTOMER')"),
    ],
    normalizationContext: ['groups' => ['redemption:read']],
    denormalizationContext: ['groups' => ['redemption:write']]
)]
class Redemption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['redemption:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'redemptions')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    #[Groups(['redemption:read'])]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'redemptions', targetEntity: Reward::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['redemption:read', 'redemption:write'])]
    private ?Reward $reward = null;

    #[ORM\Column]
    #[Groups(['redemption:read'])]
    private ?int $pointSpent = null;

    #[ORM\Column]
    #[Groups(['redemption:read'])]
    private ?\DateTimeImmutable $redeemedAt = null;

    #[ORM\Column(length: 50)]
    #[Groups(['redemption:read'])]
    private ?string $status = 'PENDING';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?customer
    {
        return $this->customer;
    }

    public function setCustomer(?customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getReward(): ?reward
    {
        return $this->reward;
    }

    public function setReward(?reward $reward): static
    {
        $this->reward = $reward;

        return $this;
    }

    public function getPointSpent(): ?int
    {
        return $this->pointSpent;
    }

    public function setPointSpent(int $pointSpent): static
    {
        $this->pointSpent = $pointSpent;

        return $this;
    }

    public function getRedeemedAt(): ?\DateTimeImmutable
    {
        return $this->redeemedAt;
    }

    public function setRedeemedAt(\DateTimeImmutable $redeemedAt): static
    {
        $this->redeemedAt = $redeemedAt;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
