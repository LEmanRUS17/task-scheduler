<?php

declare(strict_types=1);

namespace App\AuditLogFeature\Presentation\Controller;

use App\AuditLogFeature\Domain\Service\AuditActivityEventCatalog;
use App\AuditLogFeatureApi\Contract\AuditLogServiceInterface;
use App\AuditLogFeatureApi\DTOResponse\AuditActivityDayResponseInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns per-day counts of named business events (see AuditActivityEventCatalog) for the
 * current user, for building a GitHub-style contribution calendar on the client.
 */
#[AsController]
final class GetMyActivityCalendarController
{
    private const DEFAULT_RANGE_DAYS = 364;
    private const MAX_RANGE_DAYS = 400;

    public function __construct(
        private readonly AuditLogServiceInterface $auditLogService,
        private readonly Security $security,
    ) {
    }

    #[Route('/audit-log/activity', name: 'audit_log_my_activity_calendar', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        /** @var SecurityUser $securityUser */
        $securityUser = $this->security->getUser();
        $userId = $securityUser->getDomainUser()->id()->value();

        $fromParam = $request->query->get('from');
        $toParam = $request->query->get('to');

        $to = $toParam !== null
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $toParam)
            : new \DateTimeImmutable('today');

        if ($to === false) {
            return new JsonResponse(
                ['message' => 'Parameter "to" must be in Y-m-d format'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $to = $to->setTime(0, 0, 0);

        $from = $fromParam !== null
            ? \DateTimeImmutable::createFromFormat('Y-m-d', $fromParam)
            : $to->modify('-' . self::DEFAULT_RANGE_DAYS . ' days');

        if ($from === false) {
            return new JsonResponse(
                ['message' => 'Parameter "from" must be in Y-m-d format'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $from = $from->setTime(0, 0, 0);

        if ($from > $to) {
            return new JsonResponse(
                ['message' => 'Parameter "from" must not be after "to"'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $rangeDays = $from->diff($to)->days + 1;

        if ($rangeDays > self::MAX_RANGE_DAYS) {
            return new JsonResponse(
                ['message' => sprintf('Date range must not exceed %d days', self::MAX_RANGE_DAYS)],
                Response::HTTP_BAD_REQUEST,
            );
        }

        [$events, $eventsError] = $this->resolveListParam(
            $request,
            'events',
            AuditActivityEventCatalog::allEventTypes(),
        );

        if ($eventsError !== null) {
            return new JsonResponse(['message' => $eventsError], Response::HTTP_BAD_REQUEST);
        }

        $days = $this->auditLogService->getMyActivityCalendar($userId, $from, $to, $events);

        return new JsonResponse([
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'days' => array_map(
                static fn (AuditActivityDayResponseInterface $day): array => [
                    'day' => $day->getDay(),
                    'count' => $day->getCount(),
                ],
                $days,
            ),
        ], Response::HTTP_OK);
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
}
