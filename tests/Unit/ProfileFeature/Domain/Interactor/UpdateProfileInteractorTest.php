<?php

declare(strict_types=1);

namespace App\Tests\Unit\ProfileFeature\Domain\Interactor;

use App\ProfileFeature\Domain\Entity\Profile;
use App\ProfileFeature\Domain\Event\ProfileUpdated;
use App\ProfileFeature\Domain\Interactor\UpdateProfileInteractor;
use App\ProfileFeature\Domain\Port\DomainEventDispatcherInterface;
use App\ProfileFeature\Domain\Repository\ProfileRepositoryInterface;
use App\ProfileFeature\Domain\ValueObject\Username;
use PHPUnit\Framework\TestCase;

final class UpdateProfileInteractorTest extends TestCase
{
    private Profile $profile;
    private DomainEventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->profile = Profile::create('user-1', Username::fromString('old_name'), new \DateTimeImmutable());
        $this->profile->pullDomainEvents();
        $this->eventDispatcher = $this->createStub(DomainEventDispatcherInterface::class);
    }

    public function testUpdateSavesProfile(): void
    {
        $profiles = $this->createMock(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn($this->profile);
        $profiles->expects($this->once())->method('save');

        (new UpdateProfileInteractor($profiles, $this->eventDispatcher))
            ->update('user-1', null, null, null, null, null);
    }

    public function testUpdateThrowsWhenProfileNotFound(): void
    {
        $profiles = $this->createStub(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn(null);

        $this->expectException(\DomainException::class);

        (new UpdateProfileInteractor($profiles, $this->eventDispatcher))
            ->update('user-1', null, null, null, null, null);
    }

    public function testUpdateChangesUsername(): void
    {
        $profiles = $this->createStub(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn($this->profile);
        $profiles->method('save')->willReturnCallback(function (Profile $p) {
            $this->assertSame('new_name', $p->username()?->value());
        });

        (new UpdateProfileInteractor($profiles, $this->eventDispatcher))
            ->update('user-1', 'new_name', null, null, null, null);
    }

    public function testUpdateChangesAllFields(): void
    {
        $profiles = $this->createStub(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn($this->profile);
        $profiles->method('save')->willReturnCallback(function (Profile $p) {
            $this->assertSame('new_name', $p->username()?->value());
            $this->assertSame('John', $p->firstname());
            $this->assertSame('Doe', $p->lastname());
            $this->assertSame('Michael', $p->midlname());
            $this->assertSame('Available', $p->status()?->value());
        });

        (new UpdateProfileInteractor($profiles, $this->eventDispatcher))
            ->update('user-1', 'new_name', 'John', 'Doe', 'Michael', 'Available');
    }

    public function testUpdateDoesNotSaveWhenProfileNotFound(): void
    {
        $profiles = $this->createMock(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn(null);
        $profiles->expects($this->never())->method('save');

        try {
            (new UpdateProfileInteractor($profiles, $this->eventDispatcher))
                ->update('user-1', null, null, null, null, null);
        } catch (\DomainException) {
        }
    }

    public function testUpdateDispatchesProfileUpdatedEvent(): void
    {
        $profiles = $this->createStub(ProfileRepositoryInterface::class);
        $profiles->method('findByUserId')->willReturn($this->profile);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ProfileUpdated::class));

        (new UpdateProfileInteractor($profiles, $dispatcher))
            ->update('user-1', null, null, null, null, null);
    }
}
