<?php

declare(strict_types=1);

namespace App\WorkflowFeature\Domain\Exception;

final class WorkflowAccessDeniedException extends \DomainException
{
    public static function notOwner(string $workflowId): self
    {
        return new self(sprintf('Only the workflow owner may modify workflow "%s"', $workflowId));
    }

    public static function isDefaultWorkflow(string $workflowId): self
    {
        return new self(sprintf('Default workflow "%s" cannot be modified', $workflowId));
    }
}
