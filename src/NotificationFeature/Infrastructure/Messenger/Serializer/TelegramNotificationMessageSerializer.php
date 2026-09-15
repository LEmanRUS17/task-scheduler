<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Serializer;

use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramNotificationMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Produces the flat {"chat_id": int, "text": string} payload expected by the
 * rabbitmq-telegram-bot consumer (see internal/rabbitmq/rabbitmq.go `payload`
 * struct in that project) — no Messenger envelope wrapping.
 */
final class TelegramNotificationMessageSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $data = json_decode($encodedEnvelope['body'], true, flags: JSON_THROW_ON_ERROR);

        return new Envelope(new TelegramNotificationMessage(
            chatId: $data['chat_id'],
            text: $data['text'],
        ));
    }

    public function encode(Envelope $envelope): array
    {
        /** @var TelegramNotificationMessage $message */
        $message = $envelope->getMessage();

        return [
            'body' => json_encode([
                'chat_id' => $message->chatId,
                'text' => $message->text,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }
}
