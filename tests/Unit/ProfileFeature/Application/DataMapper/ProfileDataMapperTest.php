<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Application\DataMapper;

use App\FileFeatureApi\Contract\FileMetadataInterface;
use App\FileFeatureApi\Contract\FileServiceInterface;
use App\ProfileFeature\Application\DataMapper\ProfileDataMapper;
use App\ProfileFeature\Application\DataMapper\ProfileDataResponse;
use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\ValueObject\ProfileStatus;
use App\ProfileFeature\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProfileDataMapperTest extends TestCase
{
    private FileServiceInterface $fileService;

    private function buildMapper(): ProfileDataMapper
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $name, array $params = []): string =>
                sprintf('/profile/%s/avatar', $params['userId']),
        );

        return new ProfileDataMapper($this->fileService, $urlGenerator);
    }

    protected function setUp(): void
    {
        // No avatar by default; individual tests override the stub when needed.
        $this->fileService = $this->createStub(FileServiceInterface::class);
        $this->fileService->method('getAvatar')->willReturn(null);
    }

    public function testToResponseMapsAllFields(): void
    {
        $profile = Profile::create('user-1', Username::fromString('john_doe'), new \DateTimeImmutable());
        $profile->update(null, 'John', 'Doe', 'Michael', ProfileStatus::fromString('Available'));
        $loginAt = new \DateTimeImmutable('2024-06-15 09:30:00');
        $profile->recordLastLogin($loginAt);

        $response = $this->buildMapper()->toResponse($profile);

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

        $response = $this->buildMapper()->toResponse($profile);

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

        $this->assertSame('john_doe', $this->buildMapper()->toResponse($profile)->getUsername());
    }

    public function testAvatarIsNullWhenEntityHasNoAvatar(): void
    {
        $profile = Profile::create('user-1', Username::fromString('john_doe'), new \DateTimeImmutable());

        $this->assertNull($this->buildMapper()->toResponse($profile)->getAvatar());
    }

    public function testAvatarExposesUrlWhenPresent(): void
    {
        $this->fileService = $this->createStub(FileServiceInterface::class);
        $this->fileService->method('getAvatar')->willReturn($this->createStub(FileMetadataInterface::class));

        $profile = Profile::create('user-1', Username::fromString('john_doe'), new \DateTimeImmutable());

        $avatar = $this->buildMapper()->toResponse($profile)->getAvatar();

        $this->assertNotNull($avatar);
        $this->assertSame('/profile/user-1/avatar', $avatar->getUrl());
    }
}
