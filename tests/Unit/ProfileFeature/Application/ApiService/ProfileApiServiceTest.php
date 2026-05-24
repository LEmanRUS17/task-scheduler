<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Application\ApiService;

use App\ProfileFeature\Application\ApiService\ProfileApiService;
use App\ProfileFeature\Application\DataMapper\ProfileDataMapper;
use App\ProfileFeature\Application\DTORequestValidator\ProfileValidatorInterface;
use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\Interactor\CreateProfileInteractor;
use App\ProfileFeature\Domain\Interactor\UpdateProfileInteractor;
use App\ProfileFeature\Domain\Port\ClockInterface;
use App\ProfileFeature\Domain\Repository\ProfileRepositoryInterface;
use App\ProfileFeature\Domain\ValueObject\Username;
use App\ProfileFeatureApi\DTORequest\UpdateProfileRequestInterface;
use PHPUnit\Framework\TestCase;

final class ProfileApiServiceTest extends TestCase
{
    // CreateProfileInteractor and UpdateProfileInteractor are final — instantiate with mock repos
    private function buildService(
        ProfileRepositoryInterface $profiles,
        ?ProfileValidatorInterface $validator = null,
    ): ProfileApiService {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));

        return new ProfileApiService(
            new CreateProfileInteractor($profiles, $clock),
            new UpdateProfileInteractor($profiles),
            $profiles,
            new ProfileDataMapper(),
            $validator ?? $this->createStub(ProfileValidatorInterface::class),
        );
    }

    private function makeProfile(): Profile
    {
        return Profile::create('user-1', Username::fromString('john_doe'), new \DateTimeImmutable());
    }

    // --- getByUserId ---

    public function testGetByUserIdReturnsResponse(): void
    {
        $profiles = $this->createStub(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn($this->makeProfile());

        $response = $this->buildService($profiles)->getByUserId('user-1');

        $this->assertSame('user-1', $response->getUserId());
        $this->assertSame('john_doe', $response->getUsername());
    }

    public function testGetByUserIdThrowsWhenProfileNotFound(): void
    {
        $profiles = $this->createStub(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn(null);

        $this->expectException(\DomainException::class);

        $this->buildService($profiles)->getByUserId('user-1');
    }

    // --- createForUser ---

    public function testCreateForUserSavesProfile(): void
    {
        $profiles = $this->createMock(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn(null);
        $profiles->expects($this->once())->method('save');

        $this->buildService($profiles)->createForUser('user-1');
    }

    // --- update ---

    public function testUpdateSavesProfileWhenValidationPasses(): void
    {
        $profiles = $this->createMock(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn($this->makeProfile());
        $profiles->expects($this->once())->method('save');

        $validator = $this->createStub(ProfileValidatorInterface::class);
        $validator->method('validate')->willReturn([]);

        $request = $this->createStub(UpdateProfileRequestInterface::class);
        $request->method('getUsername')->willReturn('new_name');
        $request->method('getFirstname')->willReturn(null);
        $request->method('getLastname')->willReturn(null);
        $request->method('getMidlname')->willReturn(null);
        $request->method('getStatus')->willReturn(null);

        $this->buildService($profiles, $validator)->update('user-1', $request);
    }

    public function testUpdateThrowsWhenValidationFails(): void
    {
        $profiles = $this->createStub(ProfileRepositoryInterface::class);

        $validator = $this->createStub(ProfileValidatorInterface::class);
        $validator->method('validate')->willReturn(['username' => ['Too short']]);

        $request = $this->createStub(UpdateProfileRequestInterface::class);

        $this->expectException(\InvalidArgumentException::class);

        $this->buildService($profiles, $validator)->update('user-1', $request);
    }

    public function testUpdateDoesNotSaveWhenValidationFails(): void
    {
        $profiles = $this->createMock(ProfileRepositoryInterface::class);
        $profiles->expects($this->never())->method('save');

        $validator = $this->createStub(ProfileValidatorInterface::class);
        $validator->method('validate')->willReturn(['username' => ['Too short']]);

        $request = $this->createStub(UpdateProfileRequestInterface::class);

        try {
            $this->buildService($profiles, $validator)->update('user-1', $request);
        } catch (\InvalidArgumentException) {
        }
    }
}
