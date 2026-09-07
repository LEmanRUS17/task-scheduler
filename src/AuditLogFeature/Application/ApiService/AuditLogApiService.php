<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Application\ApiService;

use App\AuditLogFeature\Application\DTOResponse\AuditActivityDayResponseDTO;
use App\AuditLogFeature\Application\DTOResponse\AuditEntryResponseDTO;
use App\AuditLogFeature\Domain\Entity\AuditEntry;
use App\AuditLogFeature\Domain\Repository\AuditEntryRepositoryInterface;
use App\AuditLogFeature\Domain\Service\AuditActivityEventCatalog;
use App\AuditLogFeature\Domain\Service\AuditEntityTypeCatalog;
use App\AuditLogFeatureApi\Contract\AuditLogServiceInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;

final class AuditLogApiService implements AuditLogServiceInterface
{
    private const TASK_CLASS = 'App\TaskFeature\Domain\Entity\Task';
    private const WORKFLOW_STATUS_FIELD = 'workflowStatus';

    public function __construct(
        private readonly AuditEntryRepositoryInterface $auditEntryRepository,
        private readonly WorkflowServiceInterface $workflowService,
    ) {
        return;
    }

    public function getMyActivity(
        string $userId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        int $limit,
        int $offset,
        array $entityTypes = [],
    ): array {
        $entityClasses = AuditEntityTypeCatalog::entityClassesFor($entityTypes);

        $entries = $this->auditEntryRepository->findByActor($userId, $from, $to, $limit, $offset, $entityClasses);
        $count = $this->auditEntryRepository->countByActor($userId, $from, $to, $entityClasses);
        $statusLabels = $this->resolveWorkflowStatusLabels($entries);

        return [
            'entries' => array_map(
                fn(AuditEntry $entry): AuditEntryResponseDTO => new AuditEntryResponseDTO(
                    $entry->id(),
                    $entry->entityClass(),
                    $entry->entityId(),
                    $entry->action(),
                    $this->withWorkflowStatusLabels($entry, $statusLabels),
                    $entry->actorId(),
                    $entry->occurredAt(),
                    $entry->title(),
                    AuditActivityEventCatalog::classify($entry),
                ),
                $entries,
            ),
            'count' => $count,
        ];
    }

    /**
     * @param AuditEntry[] $entries
     * @return array<string, string> workflow status id => label
     */
    private function resolveWorkflowStatusLabels(array $entries): array
    {
        $ids = [];
        foreach ($entries as $entry) {
            if ($entry->entityClass() !== self::TASK_CLASS) {
                continue;
            }

            foreach ($entry->changedData()[self::WORKFLOW_STATUS_FIELD] ?? [] as $statusId) {
                if (is_string($statusId) && $statusId !== '') {
                    $ids[$statusId] = true;
                }
            }
        }

        return $ids === [] ? [] : $this->workflowService->getStatusLabelsByIds(array_keys($ids));
    }

    /**
     * @param array<string, string> $statusLabels
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function withWorkflowStatusLabels(AuditEntry $entry, array $statusLabels): array
    {
        $changedData = $entry->changedData();

        if (!isset($changedData[self::WORKFLOW_STATUS_FIELD])) {
            return $changedData;
        }

        [$old, $new] = $changedData[self::WORKFLOW_STATUS_FIELD];
        $changedData[self::WORKFLOW_STATUS_FIELD] = [
            is_string($old) ? ($statusLabels[$old] ?? $old) : $old,
            is_string($new) ? ($statusLabels[$new] ?? $new) : $new,
        ];

        return $changedData;
    }

    public function getMyActivityCalendar(
        string $userId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        array $eventTypes = [],
    ): array {
        $eventTypes = $eventTypes !== [] ? $eventTypes : AuditActivityEventCatalog::allEventTypes();
        $wantedEventTypes = array_flip($eventTypes);
        $entityClasses = AuditActivityEventCatalog::entityClassesFor($eventTypes);

        $entries = $this->auditEntryRepository->findByActorInRange($userId, $from, $to, $entityClasses);

        $countsByDay = [];
        foreach ($entries as $entry) {
            $eventType = AuditActivityEventCatalog::classify($entry);

            if ($eventType === null || !isset($wantedEventTypes[$eventType])) {
                continue;
            }

            $day = $entry->occurredAt()->format('Y-m-d');
            $countsByDay[$day] = ($countsByDay[$day] ?? 0) + 1;
        }

        $days = [];
        $cursor = $to->setTime(0, 0, 0);
        $end = $from->setTime(0, 0, 0);

        while ($cursor >= $end) {
            $day = $cursor->format('Y-m-d');
            $days[] = new AuditActivityDayResponseDTO($day, $countsByDay[$day] ?? 0);
            $cursor = $cursor->modify('-1 day');
        }

        return $days;
    }
}
