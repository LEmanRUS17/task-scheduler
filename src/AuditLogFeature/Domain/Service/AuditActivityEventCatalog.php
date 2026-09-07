<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Domain\Service;

use App\AuditLogFeature\Domain\Entity\AuditEntry;

/**
 * Classifies audit entries into named business events (e.g. "a task was created").
 * To add a new event type: add its entity FQCN to ENTITY_CLASS_BY_EVENT and a matching
 * arm in classify().
 */
final class AuditActivityEventCatalog
{
    private const TASK_CLASS = 'App\TaskFeature\Domain\Entity\Task';
    private const TEAM_CLASS = 'App\TeamFeature\Domain\Entity\Team';
    private const COMMENT_CLASS = 'App\CommentFeature\Domain\Entity\Comment';

    /** @var array<string, string> event type => audited entity FQCN */
    private const ENTITY_CLASS_BY_EVENT = [
        'task_created' => self::TASK_CLASS,
        'task_closed' => self::TASK_CLASS,
        'task_completed' => self::TASK_CLASS,
        'task_status_changed' => self::TASK_CLASS,
        'team_created' => self::TEAM_CLASS,
        'comment_created' => self::COMMENT_CLASS,
    ];

    /** @return string[] all known event type names */
    public static function allEventTypes(): array
    {
        return array_keys(self::ENTITY_CLASS_BY_EVENT);
    }

    /**
     * @param string[] $eventTypes
     * @return string[] unique entity FQCNs backing these event types, for narrowing the DB query
     */
    public static function entityClassesFor(array $eventTypes): array
    {
        return array_values(array_unique(array_map(
            static fn (string $eventType): string => self::ENTITY_CLASS_BY_EVENT[$eventType],
            $eventTypes,
        )));
    }

    /**
     * Classifies an audit entry into one of the known event types.
     *
     * A task closing is split into two events by how it happened, both detectable from the
     * same audit entry: ApplyTaskTransitionInteractor sets workflowStatus and closedAt in one
     * flush when a transition lands on a final status ("task_completed"), while
     * CloseTaskInteractor only ever sets closedAt on its own ("task_closed"). Any other
     * workflowStatus change — a plain transition between non-final statuses, or one alongside a
     * reopen — falls through to "task_status_changed".
     *
     * @return string|null the matched event type, or null if the entry isn't a tracked business event
     */
    public static function classify(AuditEntry $entry): ?string
    {
        return match (true) {
            $entry->entityClass() === self::TASK_CLASS
                && $entry->action() === 'create'
                => 'task_created',
            $entry->entityClass() === self::TASK_CLASS
                && $entry->action() === 'update'
                && self::isFieldSetTransition($entry, 'closedAt')
                && isset($entry->changedData()['workflowStatus'])
                => 'task_completed',
            $entry->entityClass() === self::TASK_CLASS
                && $entry->action() === 'update'
                && self::isFieldSetTransition($entry, 'closedAt')
                => 'task_closed',
            $entry->entityClass() === self::TASK_CLASS
                && $entry->action() === 'update'
                && isset($entry->changedData()['workflowStatus'])
                => 'task_status_changed',
            $entry->entityClass() === self::TEAM_CLASS
                && $entry->action() === 'create'
                => 'team_created',
            $entry->entityClass() === self::COMMENT_CLASS
                && $entry->action() === 'create'
                => 'comment_created',
            default => null,
        };
    }

    /** True when $field's changeset went from null to a non-null value. */
    private static function isFieldSetTransition(AuditEntry $entry, string $field): bool
    {
        $change = $entry->changedData()[$field] ?? null;

        return is_array($change)
            && ($change[0] ?? null) === null
            && ($change[1] ?? null) !== null;
    }
}
