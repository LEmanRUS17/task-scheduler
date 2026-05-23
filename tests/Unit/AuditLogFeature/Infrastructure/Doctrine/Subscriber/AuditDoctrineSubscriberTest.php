<?php

declare(strict_types=1);

namespace App\Tests\Unit\AuditLogFeature\Infrastructure\Doctrine\Subscriber;

use App\AuditLogFeature\Domain\Entity\AuditEntry;
use App\AuditLogFeature\Infrastructure\Doctrine\Subscriber\AuditDoctrineSubscriber;
use App\AuditLogFeatureApi\Contract\AuditableInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuditableStub implements AuditableInterface
{
}

enum FakePriority: string
{
    case High = 'high';
    case Low  = 'low';
}

enum FakeColor
{
    case Red;
    case Blue;
}

class StringableObject
{
    public function __construct(private readonly string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

final class AuditDoctrineSubscriberTest extends TestCase
{
    /**
     * @param list<object> $insertions
     * @param list<object> $updates
     * @param list<object> $deletions
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     */
    private function buildUow(
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        string $entityId = 'entity-id-1',
        array $changeSet = [],
    ): UnitOfWork {
        $uow = $this->createStub(UnitOfWork::class);
        $uow->method('getScheduledEntityInsertions')->willReturn($insertions);
        $uow->method('getScheduledEntityUpdates')->willReturn($updates);
        $uow->method('getScheduledEntityDeletions')->willReturn($deletions);
        $uow->method('getEntityIdentifier')->willReturn([$entityId]);
        $uow->method('getEntityChangeSet')->willReturn($changeSet);

        return $uow;
    }

    /** @param list<object> $persisted */
    private function buildEm(UnitOfWork $uow, array &$persisted): EntityManagerInterface
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getUnitOfWork')->willReturn($uow);
        $em->method('getClassMetadata')->willReturn($this->createStub(ClassMetadata::class));
        $em->method('persist')->willReturnCallback(function (object $e) use (&$persisted): void {
            $persisted[] = $e;
        });

        return $em;
    }

    private function buildArgs(EntityManagerInterface $em): OnFlushEventArgs
    {
        $args = $this->createStub(OnFlushEventArgs::class);
        $args->method('getObjectManager')->willReturn($em);

        return $args;
    }

    private function noTokenSecurity(): Security
    {
        $security = $this->createStub(Security::class);
        $security->method('getToken')->willReturn(null);

        return $security;
    }

    // --- Guard: skip AuditEntry itself ---

    public function testSkipsAuditEntryItself(): void
    {
        $auditEntry = AuditEntry::record('id', AuditEntry::class, 'eid', 'create', [], null, new \DateTimeImmutable());
        $uow = $this->buildUow(insertions: [$auditEntry]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertEmpty($persisted);
    }

    // --- Guard: skip non-AuditableInterface ---

    public function testSkipsNonAuditableEntities(): void
    {
        $uow = $this->buildUow(insertions: [new \stdClass()]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertEmpty($persisted);
    }

    // --- Insertions ---

    public function testAuditsInsertedAuditableEntity(): void
    {
        $uow = $this->buildUow(insertions: [new AuditableStub()], entityId: 'eid-1');
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertCount(1, $persisted);
        $entry = $persisted[0];
        $this->assertInstanceOf(AuditEntry::class, $entry);
        $this->assertSame('create', $entry->action());
        $this->assertSame(AuditableStub::class, $entry->entityClass());
        $this->assertSame('eid-1', $entry->entityId());
        $this->assertSame([], $entry->changedData());
    }

    // --- Updates ---

    public function testAuditsUpdatedAuditableEntity(): void
    {
        $changeSet = ['title' => ['old', 'new']];
        $uow = $this->buildUow(updates: [new AuditableStub()], entityId: 'eid-2', changeSet: $changeSet);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertCount(1, $persisted);
        $entry = $persisted[0];
        $this->assertSame('update', $entry->action());
        $this->assertSame(['title' => ['old', 'new']], $entry->changedData());
    }

    // --- Deletions ---

    public function testAuditsDeletedAuditableEntity(): void
    {
        $uow = $this->buildUow(deletions: [new AuditableStub()]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertCount(1, $persisted);
        $this->assertSame('delete', $persisted[0]->action());
    }

    // --- Multiple entities, mixed types ---

    public function testAuditsOnlyAuditableAmongMixedInsertions(): void
    {
        $uow = $this->buildUow(insertions: [new \stdClass(), new AuditableStub(), new \stdClass()]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertCount(1, $persisted);
    }

    // --- Actor ID ---

    public function testActorIdIsNullWhenNoToken(): void
    {
        $uow = $this->buildUow(insertions: [new AuditableStub()]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertNull($persisted[0]->actorId());
    }

    public function testActorIdIsNullWhenNonSecurityUser(): void
    {
        $uow = $this->buildUow(insertions: [new AuditableStub()]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn($this->createStub(UserInterface::class));
        $security = $this->createStub(Security::class);
        $security->method('getToken')->willReturn($token);

        (new AuditDoctrineSubscriber($security))
            ->onFlush($this->buildArgs($em));

        $this->assertNull($persisted[0]->actorId());
    }

    public function testActorIdResolvedFromSecurityUser(): void
    {
        $userId = UserId::generate();
        $user = User::register(
            $userId,
            Email::fromString('test@example.com'),
            HashedPassword::fromHash('hashed'),
            new \DateTimeImmutable(),
        );

        $token = $this->createStub(TokenInterface::class);
        $token->method('getUser')->willReturn(new SecurityUser($user));
        $security = $this->createStub(Security::class);
        $security->method('getToken')->willReturn($token);

        $uow = $this->buildUow(insertions: [new AuditableStub()]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($security))
            ->onFlush($this->buildArgs($em));

        $this->assertSame($userId->value(), $persisted[0]->actorId());
    }

    // --- Change set normalization ---

    public function testNormalizeChangeSetDatetimeToAtom(): void
    {
        $date = new \DateTimeImmutable('2024-03-15T10:00:00+00:00');
        $uow = $this->buildUow(updates: [new AuditableStub()], changeSet: ['createdAt' => [$date, null]]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertSame(
            $date->format(\DateTimeInterface::ATOM),
            $persisted[0]->changedData()['createdAt'][0],
        );
        $this->assertNull($persisted[0]->changedData()['createdAt'][1]);
    }

    public function testNormalizeChangeSetNullPreserved(): void
    {
        $uow = $this->buildUow(updates: [new AuditableStub()], changeSet: ['field' => [null, 'value']]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertNull($persisted[0]->changedData()['field'][0]);
        $this->assertSame('value', $persisted[0]->changedData()['field'][1]);
    }

    public function testNormalizeChangeSetScalarPassedThrough(): void
    {
        $uow = $this->buildUow(updates: [new AuditableStub()], changeSet: ['count' => [42, 99]]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertSame(42, $persisted[0]->changedData()['count'][0]);
        $this->assertSame(99, $persisted[0]->changedData()['count'][1]);
    }

    public function testNormalizeChangeSetBackedEnumUsesValue(): void
    {
        $uow = $this->buildUow(
            updates: [new AuditableStub()],
            changeSet: ['priority' => [FakePriority::High, FakePriority::Low]],
        );
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertSame('high', $persisted[0]->changedData()['priority'][0]);
        $this->assertSame('low', $persisted[0]->changedData()['priority'][1]);
    }

    public function testNormalizeChangeSetUnitEnumUsesName(): void
    {
        $uow = $this->buildUow(
            updates: [new AuditableStub()],
            changeSet: ['color' => [FakeColor::Red, FakeColor::Blue]],
        );
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertSame('Red', $persisted[0]->changedData()['color'][0]);
        $this->assertSame('Blue', $persisted[0]->changedData()['color'][1]);
    }

    public function testNormalizeChangeSetObjectCastsToString(): void
    {
        $obj = new StringableObject('object-value');
        $uow = $this->buildUow(updates: [new AuditableStub()], changeSet: ['obj' => [$obj, null]]);
        $persisted = [];
        $em = $this->buildEm($uow, $persisted);

        (new AuditDoctrineSubscriber($this->noTokenSecurity()))
            ->onFlush($this->buildArgs($em));

        $this->assertSame('object-value', $persisted[0]->changedData()['obj'][0]);
    }
}
