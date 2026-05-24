<?php

declare(strict_types=1);

namespace App\Tests\Unit\AuditLogFeature\Domain\Entity;

use App\AuditLogFeature\Domain\Entity\AuditEntry;
use PHPUnit\Framework\TestCase;

final class AuditEntryTest extends TestCase
{
    public function testRecordStoresAllFields(): void
    {
        $occurredAt = new \DateTimeImmutable('2024-03-15 10:00:00');
        $changedData = ['name' => ['old', 'new']];

        $entry = AuditEntry::record(
            'entry-id-1',
            'App\SomeEntity',
            'entity-id-1',
            'update',
            $changedData,
            'actor-id-1',
            $occurredAt,
        );

        $this->assertSame('entry-id-1', $entry->id());
        $this->assertSame('App\SomeEntity', $entry->entityClass());
        $this->assertSame('entity-id-1', $entry->entityId());
        $this->assertSame('update', $entry->action());
        $this->assertSame($changedData, $entry->changedData());
        $this->assertSame('actor-id-1', $entry->actorId());
        $this->assertSame($occurredAt, $entry->occurredAt());
    }

    public function testActorIdCanBeNull(): void
    {
        $entry = AuditEntry::record(
            'entry-id-1',
            'SomeClass',
            'entity-id-1',
            'create',
            [],
            null,
            new \DateTimeImmutable(),
        );

        $this->assertNull($entry->actorId());
    }

    public function testChangedDataStoredAsIs(): void
    {
        $changedData = [
            'title'  => ['old title', 'new title'],
            'status' => [null, 'active'],
        ];

        $entry = AuditEntry::record(
            'entry-id-1',
            'SomeClass',
            'entity-id-1',
            'update',
            $changedData,
            null,
            new \DateTimeImmutable(),
        );

        $this->assertSame($changedData, $entry->changedData());
    }

    public function testEntityIdPreservesCompositeKey(): void
    {
        $entry = AuditEntry::record(
            'entry-id-1',
            'SomeClass',
            'part1,part2',
            'update',
            [],
            null,
            new \DateTimeImmutable(),
        );

        $this->assertSame('part1,part2', $entry->entityId());
    }
}
