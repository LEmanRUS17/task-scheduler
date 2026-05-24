<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Domain\Entity;

use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\Event\ProfileCreated;
use App\ProfileFeature\Domain\ValueObject\ProfileStatus;
use App\ProfileFeature\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;

final class ProfileTest extends TestCase
{
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
    }

    private function makeProfile(): Profile
    {
        return Profile::create('user-1', Username::fromString('john_doe'), $this->createdAt);
    }

    public function testCreateStoresFields(): void
    {
        $profile = $this->makeProfile();

        $this->assertSame('user-1', $profile->userId());
        $this->assertSame('john_doe', $profile->username()?->value());
        $this->assertSame($this->createdAt, $profile->createdAt());
    }

    public function testCreateRecordsProfileCreatedEvent(): void
    {
        $profile = $this->makeProfile();
        $events = $profile->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProfileCreated::class, $events[0]);
        $this->assertSame('user-1', $events[0]->userId);
    }

    public function testPullDomainEventsClearsQueue(): void
    {
        $profile = $this->makeProfile();
        $profile->pullDomainEvents();

        $this->assertEmpty($profile->pullDomainEvents());
    }

    public function testNullableFieldsDefaultToNull(): void
    {
        $profile = $this->makeProfile();

        $this->assertNull($profile->firstname());
        $this->assertNull($profile->lastname());
        $this->assertNull($profile->midlname());
        $this->assertNull($profile->status());
        $this->assertNull($profile->lastLogin());
    }

    public function testUpdateChangesAllFields(): void
    {
        $profile = $this->makeProfile();
        $profile->update(
            Username::fromString('new_name'),
            'John',
            'Doe',
            'Michael',
            ProfileStatus::fromString('Available'),
        );

        $this->assertSame('new_name', $profile->username()?->value());
        $this->assertSame('John', $profile->firstname());
        $this->assertSame('Doe', $profile->lastname());
        $this->assertSame('Michael', $profile->midlname());
        $this->assertSame('Available', $profile->status()?->value());
    }

    public function testUpdateWithNullValuesPreservesExistingFields(): void
    {
        $profile = $this->makeProfile();
        $profile->update(null, 'John', null, null, null);
        // username was 'john_doe', update with null → unchanged
        $profile->update(null, null, null, null, null);

        $this->assertSame('john_doe', $profile->username()?->value());
        $this->assertSame('John', $profile->firstname());
    }

    public function testUpdateChangesOnlyProvidedFields(): void
    {
        $profile = $this->makeProfile();
        $profile->update(null, 'John', null, null, null);

        $this->assertSame('john_doe', $profile->username()?->value());
        $this->assertSame('John', $profile->firstname());
        $this->assertNull($profile->lastname());
    }

    public function testRecordLastLoginStoresDatetime(): void
    {
        $profile = $this->makeProfile();
        $loginAt = new \DateTimeImmutable('2024-06-15 09:30:00');
        $profile->recordLastLogin($loginAt);

        $this->assertSame($loginAt, $profile->lastLogin());
    }
}
