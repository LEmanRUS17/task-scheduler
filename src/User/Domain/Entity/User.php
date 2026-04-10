<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Event\UserRegistered;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\Role;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserStatus;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $password;

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    private UserStatus $status;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'password_updated_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordUpdatedAt = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    private array $domainEvents = [];

    private function __construct(
        UserId $id,
        Email $email,
        HashedPassword $password,
        Role ...$roles,
    ) {
        $this->id = $id->value();
        $this->email = $email->value();
        $this->password = $password->value();
        $this->roles = array_map(fn(Role $r) => $r->value, $roles);
        $this->status = UserStatus::ACTIVE;
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function register(
        UserId $id,
        Email $email,
        HashedPassword $password,
    ): self {
        $user = new self($id, $email, $password, Role::User);
        $user->recordEvent(new UserRegistered($id, $email));

        return $user;
    }

    public function id(): UserId
    {
        return UserId::fromString($this->id);
    }

    public function email(): Email
    {
        return Email::fromString($this->email);
    }

    public function password(): HashedPassword
    {
        return HashedPassword::fromHash($this->password);
    }

    public function status(): UserStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function passwordUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->passwordUpdatedAt;
    }

    /**
     * @return string[]
     */
    public function roles(): array
    {
        return $this->roles;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
