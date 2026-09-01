<?php

declare(strict_types=1);

namespace App\Tests\Unit\AuditLogFeature\Domain\Service;

use App\AuditLogFeature\Domain\Entity\AuditEntry;
use App\AuditLogFeature\Domain\Service\AuditActivityEventCatalog;
use PHPUnit\Framework\TestCase;

final class AuditActivityEventCatalogTest extends TestCase
{
    private const TASK_CLASS = 'App\TaskFeature\Domain\Entity\Task';

    public function testClassifiesTaskCreation(): void
    {
        $entry = $this->makeEntry(self::TASK_CLASS, 'create', []);

        $this->assertSame('task_created', AuditActivityEventCatalog::classify($entry));
    }

    public function testClassifiesTaskClosedWhenOnlyClosedAtChanges(): void
    {
        $entry = $this->makeEntry(self::TASK_CLASS, 'update', [
            'closedAt' => [null, '2024-05-01T10:00:00+00:00'],
        ]);

        $this->assertSame('task_closed', AuditActivityEventCatalog::classify($entry));
    }

    public function testClassifiesTaskCompletedWhenWorkflowStatusChangesAlongsideClosedAt(): void
    {
        $entry = $this->makeEntry(self::TASK_CLASS, 'update', [
            'workflowStatus' => ['in_progress', 'done'],
            'closedAt' => [null, '2024-05-01T10:00:00+00:00'],
        ]);

        $this->assertSame('task_completed', AuditActivityEventCatalog::classify($entry));
    }

    public function testClassifiesPlainWorkflowTransitionAsStatusChanged(): void
    {
        $entry = $this->makeEntry(self::TASK_CLASS, 'update', [
            'workflowStatus' => ['todo', 'in_progress'],
        ]);

        $this->assertSame('task_status_changed', AuditActivityEventCatalog::classify($entry));
    }

    public function testClassifiesStatusChangeAlongsideReopenAsStatusChanged(): void
    {
        $entry = $this->makeEntry(self::TASK_CLASS, 'update', [
            'workflowStatus' => ['done', 'in_progress'],
            'closedAt' => ['2024-05-01T10:00:00+00:00', null],
        ]);

        $this->assertSame('task_status_changed', AuditActivityEventCatalog::classify($entry));
    }

    public function testDoesNotClassifyReopeningAsClosingEvent(): void
    {
        $entry = $this->makeEntry(self::TASK_CLASS, 'update', [
            'closedAt' => ['2024-05-01T10:00:00+00:00', null],
        ]);

        $this->assertNull(AuditActivityEventCatalog::classify($entry));
    }

    public function testReturnsNullForUntrackedEntry(): void
    {
        $entry = $this->makeEntry('App\SomeFeature\Domain\Entity\Other', 'update', []);

        $this->assertNull(AuditActivityEventCatalog::classify($entry));
    }

    public function testAllEventTypesIncludesTaskCompleted(): void
    {
        $this->assertContains('task_completed', AuditActivityEventCatalog::allEventTypes());
    }

    public function testAllEventTypesIncludesTaskStatusChanged(): void
    {
        $this->assertContains('task_status_changed', AuditActivityEventCatalog::allEventTypes());
    }

    /**
     * @param array<string, array{0: mixed, 1: mixed}> $changedData
     */
    private function makeEntry(string $entityClass, string $action, array $changedData): AuditEntry
    {
        return AuditEntry::record(
            'entry-id-1',
            $entityClass,
            'entity-id-1',
            $action,
            $changedData,
            'actor-id-1',
            new \DateTimeImmutable('2024-05-01 10:00:00'),
        );
    }
}
