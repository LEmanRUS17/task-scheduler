<?php

declare(strict_types=1);

namespace App\ProfileFeature\Domain\Entity;

use App\ProfileFeature\Domain\Event\ProfileCreated;
use App\ProfileFeature\Domain\ValueObject\ProfileId;
use App\ProfileFeature\Domain\ValueObject\ProfileStatus;
use App\ProfileFeature\Domain\ValueObject\Username;

final class Profile
{
    private string $id;
    private string $userId;
    private ?string $username = null;
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $midlname = null;
    private ?string $status = null;
    private ?\DateTimeImmutable $lastLogin = null;
    private \DateTimeImmutable $createdAt;

    private array $domainEvents = [];

    private function __construct(
        ProfileId $id,
        string $userId,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->userId = $userId;
        $this->createdAt = $createdAt;
    }

    public static function create(
        ProfileId $id,
        string $userId,
        \DateTimeImmutable $createdAt,
    ): self {
        $profile = new self($id, $userId, $createdAt);
        $profile->recordEvent(new ProfileCreated($id, $userId));

        return $profile;
    }

    public function update(
        ?Username $username,
        ?string $firstname,
        ?string $lastname,
        ?string $midlname,
        ?ProfileStatus $status,
    ): void {
        if ($username !== null) {
            $this->username = $username->value();
        }

        if ($firstname !== null) {
            $this->firstname = $firstname;
        }

        if ($lastname !== null) {
            $this->lastname = $lastname;
        }

        if ($midlname !== null) {
            $this->midlname = $midlname;
        }

        if ($status !== null) {
            $this->status = $status->value();
        }
    }

    public function recordLastLogin(\DateTimeImmutable $at): void
    {
        $this->lastLogin = $at;
    }

    public function id(): ProfileId
    {
        return ProfileId::fromString($this->id);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function username(): ?Username
    {
        return $this->username !== null ? Username::fromString($this->username) : null;
    }

    public function firstname(): ?string
    {
        return $this->firstname;
    }

    public function lastname(): ?string
    {
        return $this->lastname;
    }

    public function midlname(): ?string
    {
        return $this->midlname;
    }

    public function status(): ?ProfileStatus
    {
        return $this->status !== null ? ProfileStatus::fromString($this->status) : null;
    }

    public function lastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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
