<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\User\Domain\Event\UserRegistered;
use App\User\Domain\ValueObject\Email;
use App\User\Domain\ValueObject\HashedPassword;
use App\User\Domain\ValueObject\Role;
use App\User\Domain\ValueObject\UserId;
use App\User\Domain\ValueObject\UserStatus;

final class User
{
    private string $id;
    private string $email;
    private string $password;
    private UserStatus $status;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $deletedAt = null;
    private ?\DateTimeImmutable $passwordUpdatedAt = null;

    /**
     * @var string[]
     */
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
