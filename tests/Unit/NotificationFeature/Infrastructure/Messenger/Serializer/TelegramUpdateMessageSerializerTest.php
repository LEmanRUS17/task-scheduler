<?php

declare(strict_types=1);

namespace App\Tests\Unit\NotificationFeature\Infrastructure\Messenger\Serializer;

use App\NotificationFeature\Infrastructure\Messenger\Message\TelegramUpdateMessage;
use App\NotificationFeature\Infrastructure\Messenger\Serializer\TelegramUpdateMessageSerializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;

final class TelegramUpdateMessageSerializerTest extends TestCase
{
    private TelegramUpdateMessageSerializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new TelegramUpdateMessageSerializer();
    }

    public function testDecodeReadsRawUpdatePublishedByPoller(): void
    {
        $json = '{"update_id":100,"message":{"message_id":5,'
            . '"chat":{"id":1450870602,"type":"private"},"text":"/start"}}';

        $envelope = $this->serializer->decode(['body' => $json, 'headers' => []]);
        /** @var TelegramUpdateMessage $message */
        $message = $envelope->getMessage();

        $this->assertInstanceOf(TelegramUpdateMessage::class, $message);
        $this->assertSame(100, $message->payload['update_id']);
        $this->assertSame(1450870602, $message->payload['message']['chat']['id']);
        $this->assertSame('/start', $message->payload['message']['text']);
    }

    public function testDecodeRejectsInvalidJson(): void
    {
        $this->expectException(MessageDecodingFailedException::class);

        $this->serializer->decode(['body' => 'not json', 'headers' => []]);
    }

    public function testDecodeRejectsNonObjectJson(): void
    {
        $this->expectException(MessageDecodingFailedException::class);

        $this->serializer->decode(['body' => '"text"', 'headers' => []]);
    }

    public function testEncodeDecodeRoundtrip(): void
    {
        $payload = ['update_id' => 1, 'message' => ['chat' => ['id' => 42], 'text' => 'Привет']];

        $encoded = $this->serializer->encode(new Envelope(new TelegramUpdateMessage($payload)));
        $decoded = $this->serializer->decode($encoded)->getMessage();

        $this->assertSame('application/json', $encoded['headers']['Content-Type']);
        /** @var TelegramUpdateMessage $decoded */
        $this->assertSame($payload, $decoded->payload);
    }
}
