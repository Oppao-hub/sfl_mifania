<?php

namespace App\Entity;

use App\Repository\RedemptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RedemptionRepository::class)]
class Redemption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'redemptions')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'redemptions', targetEntity: Reward::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reward $reward = null;

    #[ORM\Column]
    private ?int $pointSpent = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $redeemedAt = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

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
