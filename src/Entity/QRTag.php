<?php

namespace App\Entity;

use App\Repository\QRTagRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QRTagRepository::class)]
class QRTag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'qrTag', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'You must select a product to link to this QR Tag.')]
    private ?Product $product = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $qrCodeValue = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Generation date cannot be empty.')]
    private ?\DateTime $dateGenerated = null;

    #[ORM\Column(length: 255)]
    private ?string $qrImagePath = null;

    public function __construct()
    {
        $this->dateGenerated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getQrCodeValue(): ?string
    {
        return $this->qrCodeValue;
    }

    public function setQrCodeValue(?string $qrCodeValue): static
    {
        $this->qrCodeValue = $qrCodeValue;
        return $this;
    }

    public function getDateGenerated(): ?\DateTime
    {
        return $this->dateGenerated;
    }

    public function setDateGenerated(?\DateTime $dateGenerated): static
    {
        $this->dateGenerated = $dateGenerated;
        return $this;
    }

    public function getQrImagePath(): ?string
    {
        return $this->qrImagePath;
    }

    public function setQrImagePath(?string $qrImagePath): static
    {
        $this->qrImagePath = $qrImagePath;
        return $this;
    }
}
