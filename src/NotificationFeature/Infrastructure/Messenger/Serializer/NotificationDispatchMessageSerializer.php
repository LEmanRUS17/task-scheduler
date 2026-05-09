<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Serializer;

use App\NotificationFeature\Domain\Notification\MessageAction;
use App\NotificationFeature\Domain\Notification\ScriptAction;
use App\NotificationFeature\Infrastructure\Messenger\Message\NotificationDispatchMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

final class NotificationDispatchMessageSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $data = json_decode($encodedEnvelope['body'], true, flags: JSON_THROW_ON_ERROR);
        $actionData = $data['action'];

        $action = match ($actionData['type']) {
            MessageAction::TYPE => new MessageAction(
                channel: $actionData['channel'],
                recipient: $actionData['recipient'],
                subject: $actionData['subject'],
                body: $actionData['body'],
            ),
            ScriptAction::TYPE => new ScriptAction(
                script: $actionData['script'],
                args: $actionData['args'] ?? [],
            ),
            default => throw new \InvalidArgumentException("Unknown action type: {$actionData['type']}"),
        };

        return new Envelope(new NotificationDispatchMessage(
            id: $data['id'],
            event: $data['event'],
            occurredAt: new \DateTimeImmutable($data['occurred_at']),
            action: $action,
        ));
    }

    public function encode(Envelope $envelope): array
    {
        /** @var NotificationDispatchMessage $message */
        $message = $envelope->getMessage();

        return [
            'body' => json_encode([
                'id' => $message->id,
                'event' => $message->event,
                'occurred_at' => $message->occurredAt->format(\DateTimeInterface::ATOM),
                'action' => $message->action->toArray(),
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }
}
