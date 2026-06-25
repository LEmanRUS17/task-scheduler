<?php

declare(strict_types=1);

namespace App\Tests\Unit\UserFeature\Application\Interactor;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Event\PasswordResetRequested;
use App\UserFeature\Domain\Interactor\RequestPasswordResetInteractor;
use App\UserFeature\Domain\Port\ClockInterface;
use App\UserFeature\Domain\Port\ConfirmationCodeGeneratorInterface;
use App\UserFeature\Domain\Port\DomainEventDispatcherInterface;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

final class RequestPasswordResetInteractorTest extends TestCase
{
    private ClockInterface $clock;
    private ConfirmationCodeGeneratorInterface $codeGenerator;

    protected function setUp(): void
    {
        $this->clock = $this->createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn(new \DateTimeImmutable('2026-01-01 12:00:00'));

        $this->codeGenerator = $this->createStub(ConfirmationCodeGeneratorInterface::class);
        $this->codeGenerator->method('generate')->willReturn('123456');
    }

    public function testRequestStoresCodeAndDispatchesEvent(): void
    {
        $user = $this->activeUser();

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn($user);
        $repository->expects($this->once())->method('save')->with($user);

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(PasswordResetRequested::class));

        $interactor = new RequestPasswordResetInteractor(
            $repository,
            $dispatcher,
            $this->clock,
            $this->codeGenerator,
        );

        $interactor->request(Email::fromString('user@example.com'));
    }

    public function testRequestSilentlyIgnoresUnknownEmail(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findByEmail')->willReturn(null);
        $repository->expects($this->never())->method('save');

        $dispatcher = $this->createMock(DomainEventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $interactor = new RequestPasswordResetInteractor(
            $repository,
            $dispatcher,
            $this->clock,
            $this->codeGenerator,
        );

        $interactor->request(Email::fromString('missing@example.com'));
    }

    private function activeUser(): User
    {
        $user = User::register(
            UserId::generate(),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('old-hash'),
            new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
        // Drop the UserRegistered event so the reconstituted user behaves like
        // one freshly loaded from the repository (its events already dispatched).
        $user->pullDomainEvents();

        return $user;
    }
}
