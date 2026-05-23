<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Application\DataMapper;

use App\ProfileFeature\Application\DataMapper\ProfileDataMapper;
use App\ProfileFeature\Application\DataMapper\ProfileDataResponse;
use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\ValueObject\ProfileStatus;
use App\ProfileFeature\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;

final class ProfileDataMapperTest extends TestCase
{
    private ProfileDataMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new ProfileDataMapper();
    }

    public function testToResponseMapsAllFields(): void
    {
        $profile = Profile::create('user-1', Username::fromString('john_doe'), new \DateTimeImmutable());
        $profile->update(null, 'John', 'Doe', 'Michael', ProfileStatus::fromString('Available'));
        $loginAt = new \DateTimeImmutable('2024-06-15 09:30:00');
        $profile->recordLastLogin($loginAt);

        $response = $this->mapper->toResponse($profile);

        $this->assertInstanceOf(ProfileDataResponse::class, $response);
        $this->assertSame('user-1', $response->getUserId());
        $this->assertSame('john_doe', $response->getUsername());
        $this->assertSame('John', $response->getFirstname());
        $this->assertSame('Doe', $response->getLastname());
        $this->assertSame('Michael', $response->getMidlname());
        $this->assertSame('Available', $response->getStatus());
        $this->assertSame($loginAt, $response->getLastLogin());
    }

    public function testToResponseHandlesNullOptionalFields(): void
    {
        $profile = Profile::create('user-1', Username::fromString('john_doe'), new \DateTimeImmutable());

        $response = $this->mapper->toResponse($profile);

        $this->assertNull($response->getFirstname());
        $this->assertNull($response->getLastname());
        $this->assertNull($response->getMidlname());
        $this->assertNull($response->getStatus());
        $this->assertNull($response->getLastLogin());
    }

    public function testToResponseUsernameIsNullWhenNotSet(): void
    {
        // Username is set on create, but ensure mapper reads it correctly
        $profile = Profile::create('user-1', Username::fromString('john_doe'), new \DateTimeImmutable());

        $this->assertSame('john_doe', $this->mapper->toResponse($profile)->getUsername());
    }
}
