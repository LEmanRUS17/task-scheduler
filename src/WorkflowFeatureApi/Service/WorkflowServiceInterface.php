<?php

declare(strict_types=1);

namespace App\WorkflowFeatureApi\Service;

use App\WorkflowFeatureApi\DTORequest\AddStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\AddTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\CreateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateStatusRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateTransitionRequestInterface;
use App\WorkflowFeatureApi\DTORequest\UpdateWorkflowRequestInterface;
use App\WorkflowFeatureApi\DTOResponse\AttachedTeamResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\TeamWorkflowResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowListMiniResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowStatusResponseInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowTransitionResponseInterface;

interface WorkflowServiceInterface
{
    public function create(CreateWorkflowRequestInterface $request, string $createdBy): WorkflowResponseInterface;

    /**
     * Creates the default workflow (statuses "открыт"/"закрыт") for a newly registered user, if one
     * does not already exist for them.
     */
    public function createDefaultForUser(string $userId): WorkflowResponseInterface;

    public function getDefaultForUser(string $userId): ?WorkflowResponseInterface;

    /**
     * @throws \InvalidArgumentException if $request fails validation
     * @throws \DomainException if the workflow does not exist or $userId is not its creator
     */
    public function update(
        string $id,
        UpdateWorkflowRequestInterface $request,
        string $userId,
    ): WorkflowResponseInterface;

    public function getById(string $id): ?WorkflowResponseInterface;

    /** @return WorkflowResponseInterface[] */
    public function getList(): array;

    /**
     * Returns a single page of the caller's own workflows ordered by creation date (newest
     * first), with the caller's own default workflow pinned first on the initial page (offset 0).
     *
     * When $teamId is given, the caller must be a member of that team (verified by the caller of
     * this method); the team's workflows attached via {@see attachToTeam()} are additionally
     * surfaced on the initial page (offset 0), deduplicated against the caller's own workflows.
     *
     * When $includeDefault is false, the caller's default workflow is left out entirely (it is
     * never pinned, and never counted by {@see countAll()} either).
     *
     * When $inTeamId is given, each returned workflow reports via {@see WorkflowResponseInterface::isInTeam()}
     * whether it is currently attached to that team; this is independent of $teamId.
     *
     * @return WorkflowResponseInterface[]
     */
    public function getPage(
        int $limit,
        int $offset,
        string $userId,
        ?string $teamId = null,
        bool $includeDefault = true,
        ?string $inTeamId = null,
    ): array;

    /** Counts the same set as {@see getPage()}, including the caller's own default workflow unless $includeDefault is false. */
    public function countAll(string $userId, ?string $teamId = null, bool $includeDefault = true): int;

    /**
     * Returns workflows for the given ids, preserving the order of the ids.
     *
     * @param list<string> $ids
     * @return WorkflowResponseInterface[]
     */
    public function getByIds(array $ids): array;

    /**
     * @throws \InvalidArgumentException if $request fails validation
     * @throws \DomainException if the workflow does not exist or $userId is not its creator
     */
    public function addStatus(
        string $workflowId,
        AddStatusRequestInterface $request,
        string $userId,
    ): WorkflowStatusResponseInterface;

    /**
     * @throws \InvalidArgumentException if $request fails validation
     * @throws \DomainException if the workflow does not exist or $userId is not its creator
     */
    public function updateStatus(
        string $workflowId,
        string $statusId,
        UpdateStatusRequestInterface $request,
        string $userId,
    ): WorkflowStatusResponseInterface;

    public function getStatusById(string $workflowId, string $statusId): ?WorkflowStatusResponseInterface;

    /** @return WorkflowStatusResponseInterface[] */
    public function getStatuses(string $workflowId): array;

    /**
     * @throws \InvalidArgumentException if $request fails validation
     * @throws \DomainException if the workflow does not exist or $userId is not its creator
     */
    public function addTransition(
        string $workflowId,
        AddTransitionRequestInterface $request,
        string $userId,
    ): WorkflowTransitionResponseInterface;

    /**
     * @throws \InvalidArgumentException if $request fails validation
     * @throws \DomainException if the workflow does not exist or $userId is not its creator
     */
    public function updateTransition(
        string $workflowId,
        string $transitionId,
        UpdateTransitionRequestInterface $request,
        string $userId,
    ): WorkflowTransitionResponseInterface;

    public function getTransitionById(
        string $workflowId,
        string $transitionId,
    ): ?WorkflowTransitionResponseInterface;

    /** @return WorkflowTransitionResponseInterface[] */
    public function getTransitions(string $workflowId): array;

    /**
     * Returns the workflow's transitions (id + name) available for notification
     * subscriptions, along with the total number of transitions.
     */
    public function listTransactionByWorkflow(string $workflowId): WorkflowListMiniResponseInterface;

    /**
     * Attaches a workflow to a team, making it available for the team's members to pick.
     * Idempotent when already attached.
     *
     * @throws \DomainException if the workflow does not exist, $userId is not its creator, or
     *     the workflow is the caller's default workflow
     */
    public function attachToTeam(string $workflowId, string $teamId, string $userId): void;

    /**
     * Detaches a workflow from a team; existing tasks created with it keep their reference.
     *
     * @throws \DomainException if the workflow does not exist, $userId is not its creator, or it
     *     is not attached to the team
     */
    public function detachFromTeam(string $workflowId, string $teamId, string $userId): void;

    /**
     * Returns every workflow attached to a team, together with who attached it (always the
     * workflow's owner, see {@see attachToTeam()}) and how many of the team's tasks currently use
     * it, newest attachment first.
     *
     * @return TeamWorkflowResponseInterface[]
     */
    public function getTeamWorkflows(string $teamId): array;

    /**
     * Returns every team a workflow is attached to, newest attachment first.
     *
     * @return AttachedTeamResponseInterface[]
     */
    public function getWorkflowTeams(string $workflowId): array;
}
