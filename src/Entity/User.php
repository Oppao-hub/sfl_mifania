<?php

namespace App\Entity;

use App\Entity\Enum\AccountStatus;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account registered with this email address.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Please enter an email address.')]
    #[Assert\Email(message: 'Please provide a valid email format (e.g., name@mifania.com).')]
    #[Groups(['product:read'])]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Admin::class, cascade: ['persist', 'remove'])]
    private ?Admin $admin = null;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'recipient')]
    private Collection $notifications;

    #[ORM\OneToMany(targetEntity: Stock::class, mappedBy: 'addedBy')]
    private Collection $stocks;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Customer $customer = null;

    #[ORM\OneToMany(targetEntity: ActivityLog::class, mappedBy: 'user')]
    private Collection $activityLogs;

    #[ORM\Column(length: 20)]
    #[Assert\NotNull(message: 'Account status must be defined.')]
    private AccountStatus $status = AccountStatus::Active;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Staff $staff = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $isVerified = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationToken = null;

    public function __construct()
    {
        $this->notifications = new ArrayCollection();
        $this->stocks = new ArrayCollection();
        $this->activityLogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles; $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(?array $roles): static
    {
        $this->roles = $roles ?? []; return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    // ADDED '?': Safe handling of null passwords during updates
    public function setPassword(?string $password): static
    {
        $this->password = $password; return $this;
    }

    public function __serialize(): array { $data = (array) $this; $data["\0" . self::class . "\0password"] = hash('crc32c', (string) $this->password); return $data; }

    public function eraseCredentials(): void { }

    public function getAdmin(): ?Admin
    {
        return $this->admin;
    }
    public function setAdmin(?Admin $admin): static
    {
        if ($admin === null && $this->admin !== null) { $this->admin->setUser(null); }
        if ($admin !== null && $admin->getUser() !== $this) { $admin->setUser($this); }
        $this->admin = $admin;
        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }
    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) { $this->notifications->add($notification); $notification->setRecipient($this); }
        return $this;
    }
    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) { if ($notification->getRecipient() === $this) { $notification->setRecipient(null); } }
        return $this;
    }

    public function getStocks(): Collection { return $this->stocks; }
    public function addStock(Stock $stock): static
    {
        if (!$this->stocks->contains($stock)) { $this->stocks->add($stock); $stock->setAddedBy($this); }
        return $this;
    }
    public function removeStock(Stock $stock): static
    {
        if ($this->stocks->removeElement($stock)) { if ($stock->getAddedBy() === $this) { $stock->setAddedBy(null); } }
        return $this;
    }

    public function getCustomer(): ?Customer { return $this->customer; }
    public function setCustomer(?Customer $customer): static
    {
        if ($customer === null && $this->customer !== null) { $this->customer->setUser(null); }
        if ($customer !== null && $customer->getUser() !== $this) { $customer->setUser($this); }
        $this->customer = $customer;
        return $this;
    }

    public function getActivityLogs(): Collection
    {
        return $this->activityLogs;
    }
    public function addActivityLog(ActivityLog $activityLog): static
    {
        if (!$this->activityLogs->contains($activityLog)) { $this->activityLogs->add($activityLog); $activityLog->setUser($this); }
        return $this;
    }
    public function removeActivityLog(ActivityLog $activityLog): static
    {
        if ($this->activityLogs->removeElement($activityLog)) { if ($activityLog->getUser() === $this) { $activityLog->setUser(null); } }
        return $this;
    }

    public function getStatus(): AccountStatus
    {
        return $this->status;
    }

    public function setStatus(?AccountStatus $status): self
    {
        $this->status = $status ?? AccountStatus::Active;
        return $this;
    }

    public function getStaff(): ?Staff
    {
        return $this->staff;
    }
    public function setStaff(?Staff $staff): static
    {
        if ($staff === null && $this->staff !== null) { $this->staff->setUser(null); }
        if ($staff !== null && $staff->getUser() !== $this) { $staff->setUser($this); }
        $this->staff = $staff;
        return $this;
    }

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): self
    {
        $this->isVerified = $isVerified ?? false;
        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;
        return $this;
    }
}
