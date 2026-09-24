<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Messenger\Serializer;

use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramUpdateMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Reads the raw Telegram Update JSON published by the rabbitmq-telegram-bot poller
 * (cmd/poller/main.go marshals each getUpdates item as-is) — no Messenger envelope wrapping.
 */
final class TelegramUpdateMessageSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        try {
            $data = json_decode($encodedEnvelope['body'], true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new MessageDecodingFailedException('Telegram update is not valid JSON.', 0, $e);
        }

        if (!\is_array($data)) {
            throw new MessageDecodingFailedException('Telegram update must be a JSON object.');
        }

        return new Envelope(new TelegramUpdateMessage($data));
    }

    public function encode(Envelope $envelope): array
    {
        /** @var TelegramUpdateMessage $message */
        $message = $envelope->getMessage();

        return [
            'body' => json_encode($message->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'headers' => ['Content-Type' => 'application/json'],
        ];
    }
}
