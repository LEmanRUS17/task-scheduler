<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Presentation\Controller;

use App\AuditLogFeature\Domain\Service\AuditEntityTypeCatalog;
use App\AuditLogFeatureApi\Contract\AuditLogServiceInterface;
use App\AuditLogFeatureApi\DTOResponse\AuditEntryResponseInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class GetMyAuditLogController
{
    private const DEFAULT_LIMIT = 20;
    private const ALLOWED_LIMITS = [10, 20, 50, 100];

    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
        private readonly Security $security,
    ) {
    }

    #[Route('/audit-log', name: 'audit_log_my_activity', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        [$from, $to, $error] = $this->resolveDateRange($request);

        if ($error !== null) {
            return new JsonResponse(['message' => $error], Response::HTTP_BAD_REQUEST);
        }

        [$types, $typesError] = $this->resolveListParam($request, 'types', AuditEntityTypeCatalog::allTypes());

        if ($typesError !== null) {
            return new JsonResponse(['message' => $typesError], Response::HTTP_BAD_REQUEST);
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = $this->resolveLimit($request->query->getInt('limit', self::DEFAULT_LIMIT));
        $offset = ($page - 1) * $limit;

        $result = $this->auditLogService->getMyActivity($userId, $from, $to, $limit, $offset, $types);
        $count = $result['count'];

        return new JsonResponse([
            'entries' => array_map(
                static fn (AuditEntryResponseInterface $entry): array => [
                    'id' => $entry->getId(),
                    'entityClass' => $entry->getEntityClass(),
                    'entityId' => $entry->getEntityId(),
                    'title' => $entry->getTitle(),
                    'action' => $entry->getAction(),
                    'eventType' => $entry->getEventType(),
                    'changedData' => $entry->getChangedData(),
                    'occurredAt' => $entry->getOccurredAt()->format(\DateTimeInterface::ATOM),
                ],
                $result['entries'],
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'pages' => $limit > 0 ? (int) ceil($count / $limit) : 0,
            ],
            'count' => $count,
        ], Response::HTTP_OK);
    }

    private function resolveLimit(int $limit): int
    {
        return in_array($limit, self::ALLOWED_LIMITS, true) ? $limit : self::DEFAULT_LIMIT;
    }

    /**
     * @param string[] $allowed
     * @return array{0: string[], 1: ?string}
     */
    private function resolveListParam(Request $request, string $name, array $allowed): array
    {
        $raw = $request->query->get($name);

        if ($raw === null || $raw === '') {
            return [[], null];
        }

        $values = array_values(array_unique(array_filter(array_map('trim', explode(',', $raw)))));
        $invalid = array_diff($values, $allowed);

        if ($invalid !== []) {
            return [
                [],
                sprintf(
                    'Parameter "%s" contains invalid value(s): %s. Allowed: %s',
                    $name,
                    implode(', ', $invalid),
                    implode(', ', $allowed),
                ),
            ];
        }

        return [$values, null];
    }

    /** @return array{0: ?\DateTimeImmutable, 1: ?\DateTimeImmutable, 2: ?string} */
    private function resolveDateRange(Request $request): array
    {
        $fromParam = $request->query->get('from');
        $toParam = $request->query->get('to');

        $from = null;
        $to = null;

        if ($fromParam !== null) {
            $from = \DateTimeImmutable::createFromFormat('Y-m-d', $fromParam);

            if ($from === false) {
                return [null, null, 'Parameter "from" must be in Y-m-d format'];
            }

            $from = $from->setTime(0, 0, 0);
        }

        if ($toParam !== null) {
            $to = \DateTimeImmutable::createFromFormat('Y-m-d', $toParam);

            if ($to === false) {
                return [null, null, 'Parameter "to" must be in Y-m-d format'];
            }

            $to = $to->setTime(23, 59, 59);
        }

        if ($from !== null && $to !== null && $from >= $to) {
            return [null, null, 'Parameter "from" must be before "to"'];
        }

        return [$from, $to, null];
    }
}
