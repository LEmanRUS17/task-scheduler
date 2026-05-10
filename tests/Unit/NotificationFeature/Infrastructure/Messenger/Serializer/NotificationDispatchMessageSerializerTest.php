<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Serializer;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Domain\Notification\ScriptAction;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use App\NotificationFeature\Infrastructure\Messenger\Serializer\NotificationDispatchMessageSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

final class NotificationDispatchMessageSerializerTest extends TestCase
{
    private NotificationDispatchMessageSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new NotificationDispatchMessageSerializer();
    }

    public function testEncodeMessageActionProducesCleanJson(): void
    {
        $message = NotificationDispatchMessage::create(
            event: 'task.created',
            action: new MessageAction(
                channel: 'email',
                recipient: 'user@example.com',
                subject: 'Task created',
                body: 'Your task has been successfully created.',
            ),
        );

        $encoded = $this->serializer->encode(new Envelope($message));

        $this->assertSame('application/json', $encoded['headers']['Content-Type']);

        $body = json_decode($encoded['body'], true);
        $this->assertSame($message->id, $body['id']);
        $this->assertSame('task.created', $body['event']);
        $this->assertArrayHasKey('occurred_at', $body);

        $action = $body['action'];
        $this->assertSame('message', $action['type']);
        $this->assertSame('email', $action['channel']);
        $this->assertSame('user@example.com', $action['recipient']);
        $this->assertSame('Task created', $action['subject']);
        $this->assertSame('Your task has been successfully created.', $action['body']);

        // Нет Symfony-специфичных обёрток
        $this->assertArrayNotHasKey('stamps', $body);
        $this->assertArrayNotHasKey('type', $body);
    }

    public function testEncodeScriptActionProducesCleanJson(): void
    {
        $message = NotificationDispatchMessage::create(
            event: 'task.status_changed',
            action: new ScriptAction(
                script: 'notify_crm.sh',
                args: ['task_id' => 'abc-123', 'status' => 'done'],
            ),
        );

        $encoded = $this->serializer->encode(new Envelope($message));
        $body = json_decode($encoded['body'], true);

        $action = $body['action'];
        $this->assertSame('script', $action['type']);
        $this->assertSame('notify_crm.sh', $action['script']);
        $this->assertSame(['task_id' => 'abc-123', 'status' => 'done'], $action['args']);
    }

    public function testDecodeRestoresMessageAction(): void
    {
        $occurredAt = new \DateTimeImmutable('2026-05-09T10:00:00+00:00');
        $json = json_encode([
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'event' => 'task.assignee_added',
            'occurred_at' => $occurredAt->format(\DateTimeInterface::ATOM),
            'action' => [
                'type' => 'message',
                'channel' => 'email',
                'recipient' => 'assignee@example.com',
                'subject' => 'You have been assigned',
                'body' => 'You have been assigned to task "Fix bug".',
            ],
        ]);

        $envelope = $this->serializer->decode(['body' => $json, 'headers' => []]);
        /** @var NotificationDispatchMessage $message */
        $message = $envelope->getMessage();

        $this->assertInstanceOf(NotificationDispatchMessage::class, $message);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $message->id);
        $this->assertSame('task.assignee_added', $message->event);

        $this->assertInstanceOf(MessageAction::class, $message->action);
        $this->assertSame('email', $message->action->channel);
        $this->assertSame('assignee@example.com', $message->action->recipient);
    }

    public function testDecodeRestoresScriptAction(): void
    {
        $json = json_encode([
            'id' => '550e8400-e29b-41d4-a716-446655440001',
            'event' => 'task.status_changed',
            'occurred_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'action' => [
                'type' => 'script',
                'script' => 'sync_jira.sh',
                'args' => ['task_id' => 'abc', 'from' => 'open', 'to' => 'done'],
            ],
        ]);

        $envelope = $this->serializer->decode(['body' => $json, 'headers' => []]);
        /** @var NotificationDispatchMessage $message */
        $message = $envelope->getMessage();

        $this->assertInstanceOf(ScriptAction::class, $message->action);
        $this->assertSame('sync_jira.sh', $message->action->script);
        $this->assertSame(['task_id' => 'abc', 'from' => 'open', 'to' => 'done'], $message->action->args);
    }

    public function testEncodeDecodeRoundtrip(): void
    {
        $original = NotificationDispatchMessage::create(
            event: 'task.created',
            action: new MessageAction(
                channel: 'email',
                recipient: 'test@example.com',
                subject: 'Hello',
                body: 'World',
            ),
        );

        $encoded = $this->serializer->encode(new Envelope($original));
        $decoded = $this->serializer->decode($encoded)->getMessage();

        /** @var NotificationDispatchMessage $decoded */
        $this->assertSame($original->id, $decoded->id);
        $this->assertSame($original->event, $decoded->event);
        $this->assertSame($original->action->getType(), $decoded->action->getType());
    }

    public function testDecodeThrowsOnUnknownActionType(): void
    {
        $json = json_encode([
            'id' => 'uuid',
            'event' => 'test',
            'occurred_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'action' => ['type' => 'unknown_type'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->serializer->decode(['body' => $json, 'headers' => []]);
    }
}
