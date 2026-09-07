<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Infrastructure\Doctrine\Subscriber;

use App\AuditLogFeature\Domain\Entity\AuditEntry;
use App\AuditLogFeature\Infrastructure\Messenger\Message\RecordAuditEntryMessage;
use App\AuditLogFeatureApi\Contract\AuditableInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;

final class AuditDoctrineSubscriber
{
    /** @var list<AuditEntry> entries recorded during this flush, relayed to ClickHouse once it commits */
    private array $pendingEntries = [];

    public function __construct(
        private readonly Security $security,
        private readonly MessageBusInterface $defaultBus,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();
        $actorId = $this->resolveActorId();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->audit($em, $uow, $entity, 'create', [], $actorId);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->audit($em, $uow, $entity, 'update', $uow->getEntityChangeSet($entity), $actorId);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->audit($em, $uow, $entity, 'delete', [], $actorId);
        }
    }

    /**
     * Relays entries recorded by this flush to ClickHouse, async and only once Postgres has
     * actually committed them — postFlush does not run if the flush failed.
     */
    public function postFlush(PostFlushEventArgs $args): void
    {
        foreach ($this->pendingEntries as $entry) {
            $this->defaultBus->dispatch(new RecordAuditEntryMessage(
                $entry->id(),
                $entry->entityClass(),
                $entry->entityId(),
                $entry->action(),
                json_encode($entry->changedData(), JSON_THROW_ON_ERROR),
                $entry->actorId() ?? '',
                $entry->occurredAt(),
                $entry->title() ?? '',
            ));
        }

        $this->pendingEntries = [];
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changedData
     */
    private function audit(
        EntityManagerInterface $em,
        UnitOfWork $uow,
        object $entity,
        string $action,
        array $changedData,
        ?string $actorId,
    ): void {
        if ($entity instanceof AuditEntry) {
            return;
        }

        if (!$entity instanceof AuditableInterface) {
            return;
        }

        $identifiers = $uow->getEntityIdentifier($entity);
        $entityId = implode(',', array_map('strval', $identifiers));

        $entry = AuditEntry::record(
            $this->generateUuid(),
            get_class($entity),
            $entityId,
            $action,
            $this->normalizeChangeSet($changedData),
            $actorId,
            new \DateTimeImmutable(),
            $entity->auditTitle(),
        );

        $em->persist($entry);
        $uow->computeChangeSet($em->getClassMetadata(AuditEntry::class), $entry);
        $this->pendingEntries[] = $entry;
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changeSet
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function normalizeChangeSet(array $changeSet): array
    {
        $result = [];
        foreach ($changeSet as $field => [$old, $new]) {
            $result[$field] = [$this->normalizeValue($old), $this->normalizeValue($new)];
        }

        return $result;
    }

    private function normalizeValue(mixed $value): string|int|float|bool|null
    {
        return match (true) {
            $value === null => null,
            $value instanceof \DateTimeInterface => $value->format(\DateTimeInterface::ATOM),
            $value instanceof \BackedEnum => $value->value,
            $value instanceof \UnitEnum => $value->name,
            is_scalar($value) => $value,
            default => (string) $value,
        };
    }

    private function resolveActorId(): ?string
    {
        $token = $this->security->getToken();

        if ($token === null) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof SecurityUser) {
            return null;
        }

        return $user->getDomainUser()->id()->value();
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
