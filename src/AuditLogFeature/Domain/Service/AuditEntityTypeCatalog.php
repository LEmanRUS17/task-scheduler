<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Domain\Service;

/**
 * Maps human-friendly entity type names to the audited entity FQCNs, for filtering the raw
 * audit log by entity type. To add a new type, add its entity FQCN here — it must implement
 * AuditableInterface.
 */
final class AuditEntityTypeCatalog
{
    /** @var array<string, string> entity type => audited entity FQCN */
    private const ENTITY_CLASS_BY_TYPE = [
        'task' => 'App\TaskFeature\Domain\Entity\Task',
        'team' => 'App\TeamFeature\Domain\Entity\Team',
        'team_member' => 'App\TeamFeature\Domain\Entity\TeamMember',
        'user' => 'App\UserFeature\Domain\Entity\User',
        'workflow' => 'App\WorkflowFeature\Domain\Entity\WorkflowTransition',
        'workflow_team' => 'App\WorkflowFeature\Domain\Entity\WorkflowTeam',
        'comment' => 'App\CommentFeature\Domain\Entity\Comment',
    ];

    /** @return string[] all known entity type names */
    public static function allTypes(): array
    {
        return array_keys(self::ENTITY_CLASS_BY_TYPE);
    }

    /**
     * @param string[] $types
     * @return string[] unique entity FQCNs for these types
     */
    public static function entityClassesFor(array $types): array
    {
        return array_values(array_unique(array_map(
            static fn (string $type): string => self::ENTITY_CLASS_BY_TYPE[$type],
            $types,
        )));
    }
}
