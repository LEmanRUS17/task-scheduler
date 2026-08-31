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

final class AuditLogApiService implements AuditLogServiceInterface
{
    public function __construct(
        private readonly AuditEntryRepositoryInterface $auditEntryRepository,
    ) {
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

        return [
            'entries' => array_map(
                static fn (AuditEntry $entry): AuditEntryResponseDTO => new AuditEntryResponseDTO(
                    $entry->id(),
                    $entry->entityClass(),
                    $entry->entityId(),
                    $entry->action(),
                    $entry->changedData(),
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
