<?php

declare(strict_types=1);

namespace App\Tests\Unit\TaskFeature\Application\DTORequestValidator;

use App\TaskFeature\Application\DTORequest\TaskCreateRequestDTO;
use App\TaskFeature\Application\DTORequestValidator\TaskValidator;
use App\TaskFeature\Domain\Port\TeamMembershipInterface;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TaskValidatorTest extends TestCase
{
    private const WORKFLOW_ID = '11111111-1111-4111-8111-111111111111';

    public function testValidateAddsWorkflowNotFoundWhenWorkflowDoesNotExist(): void
    {
        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getById')->willReturn(null);

        $violations = $this->makeValidator($workflowService)->validate(
            $this->makeRequest(self::WORKFLOW_ID),
            'user-1',
        );

        $this->assertSame(['Workflow not found'], $violations['workflow']);
    }

    public function testValidateAddsWorkflowNotFoundWhenWorkflowIdIsMalformed(): void
    {
        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getById')->willThrowException(
            new \InvalidArgumentException('Invalid UUID v4: "not-a-uuid"'),
        );

        $violations = $this->makeValidator($workflowService)->validate(
            $this->makeRequest('not-a-uuid'),
            'user-1',
        );

        $this->assertSame(['Workflow not found'], $violations['workflow']);
    }

    public function testValidateHasNoWorkflowViolationWhenWorkflowExists(): void
    {
        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getById')->willReturn(
            $this->createStub(WorkflowResponseInterface::class),
        );

        $violations = $this->makeValidator($workflowService)->validate(
            $this->makeRequest(self::WORKFLOW_ID),
            'user-1',
        );

        $this->assertArrayNotHasKey('workflow', $violations);
    }

    public function testValidateDoesNotDuplicateBlankWorkflowViolation(): void
    {
        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->never())->method('getById');

        $violator = $this->createStub(ValidatorInterface::class);
        $violator->method('validate')->willReturn(new ConstraintViolationList([
            new \Symfony\Component\Validator\ConstraintViolation(
                'Workflow is required',
                null,
                [],
                null,
                'workflow',
                '',
            ),
        ]));

        $violations = $this->makeValidator($workflowService, $violator)->validate(
            $this->makeRequest(''),
            'user-1',
        );

        $this->assertSame(['Workflow is required'], $violations['workflow']);
    }

    private function makeRequest(string $workflow): TaskCreateRequestDTO
    {
        return new TaskCreateRequestDTO(title: 'Fix bug', workflow: $workflow);
    }

    private function makeValidator(
        WorkflowServiceInterface $workflowService,
        ?ValidatorInterface $validator = null,
    ): TaskValidator {
        $validator ??= (function (): ValidatorInterface {
            $stub = $this->createStub(ValidatorInterface::class);
            $stub->method('validate')->willReturn(new ConstraintViolationList());

            return $stub;
        })();

        $teamMembership = $this->createStub(TeamMembershipInterface::class);
        $teamMembership->method('isMember')->willReturn(true);

        return new TaskValidator($validator, $teamMembership, $workflowService);
    }
}
