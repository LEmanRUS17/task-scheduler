<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\EventSubscriber;

use App\SearchFeature\Infrastructure\Messenger\EventSubscriber\WorkflowSearchIndexSubscriber;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexWorkflowMessage;
use App\WorkflowFeature\Domain\Event\WorkflowCreated;
use App\WorkflowFeature\Domain\Event\WorkflowUpdated;
use App\WorkflowFeature\Domain\ValueObject\WorkflowId;
use App\WorkflowFeature\Domain\ValueObject\WorkflowTitle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class WorkflowSearchIndexSubscriberTest extends TestCase
{
    private const WORKFLOW_ID = '550e8400-e29b-4d4d-a716-446655440000';

    private function makeBus(?IndexWorkflowMessage &$captured = null): MessageBusInterface
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->willReturnCallback(function (IndexWorkflowMessage $message) use (&$captured) {
                $captured = $message;
                return new Envelope($message);
            });

        return $bus;
    }

    public function testOnWorkflowCreatedDispatchesIndexWorkflowMessageWithWorkflowId(): void
    {
        $workflowId = WorkflowId::fromString(self::WORKFLOW_ID);
        $event = new WorkflowCreated($workflowId, WorkflowTitle::fromString('Bug flow'), 'user-1');

        $captured = null;
        (new WorkflowSearchIndexSubscriber($this->makeBus($captured)))->onWorkflowCreated($event);

        $this->assertInstanceOf(IndexWorkflowMessage::class, $captured);
        $this->assertSame(self::WORKFLOW_ID, $captured->workflowId);
    }

    public function testOnWorkflowUpdatedDispatchesIndexWorkflowMessageWithWorkflowId(): void
    {
        $workflowId = WorkflowId::fromString(self::WORKFLOW_ID);
        $event = new WorkflowUpdated($workflowId, WorkflowTitle::fromString('Bug flow'));

        $captured = null;
        (new WorkflowSearchIndexSubscriber($this->makeBus($captured)))->onWorkflowUpdated($event);

        $this->assertInstanceOf(IndexWorkflowMessage::class, $captured);
        $this->assertSame(self::WORKFLOW_ID, $captured->workflowId);
    }
}
