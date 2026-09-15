<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Serializer;

use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramNotificationMessage;
use App\NotificationFeature\Infrastructure\Messenger\Serializer\TelegramNotificationMessageSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;

final class TelegramNotificationMessageSerializerTest extends TestCase
{
    private TelegramNotificationMessageSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new TelegramNotificationMessageSerializer();
    }

    public function testEncodeProducesFlatJsonMatchingBotPayload(): void
    {
        $message = new TelegramNotificationMessage(chatId: 1450870602, text: 'Task "Fix bug" created');

        $encoded = $this->serializer->encode(new Envelope($message));

        $this->assertSame('application/json', $encoded['headers']['Content-Type']);

        $body = json_decode($encoded['body'], true);
        $this->assertSame(['chat_id', 'text'], array_keys($body));
        $this->assertSame(1450870602, $body['chat_id']);
        $this->assertSame('Task "Fix bug" created', $body['text']);
    }

    public function testDecodeRestoresMessage(): void
    {
        $json = json_encode(['chat_id' => 1450870602, 'text' => 'Hello']);

        $envelope = $this->serializer->decode(['body' => $json, 'headers' => []]);
        /** @var TelegramNotificationMessage $message */
        $message = $envelope->getMessage();

        $this->assertInstanceOf(TelegramNotificationMessage::class, $message);
        $this->assertSame(1450870602, $message->chatId);
        $this->assertSame('Hello', $message->text);
    }

    public function testEncodeDecodeRoundtrip(): void
    {
        $original = new TelegramNotificationMessage(chatId: 42, text: 'Roundtrip');

        $encoded = $this->serializer->encode(new Envelope($original));
        $decoded = $this->serializer->decode($encoded)->getMessage();

        /** @var TelegramNotificationMessage $decoded */
        $this->assertSame($original->chatId, $decoded->chatId);
        $this->assertSame($original->text, $decoded->text);
    }
}
