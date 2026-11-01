<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Unit;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Amqp\Adapter;
use Yiisoft\Queue\Amqp\QueueProviderInterface;
use Yiisoft\Queue\Amqp\Settings\QueueSettingsInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\Serializer\MessageSerializerInterface;

final class SubscribeTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $calls = [];

    public function testAcknowledgesHandledMessage(): void
    {
        $channel = $this->createChannel([['1']]);

        $this->createAdapter($channel, [true, false])->subscribe(static fn(): bool => true);

        self::assertSame(['ack 1'], $this->calls);
    }

    public function testRequeuesRefusedMessageAndStops(): void
    {
        $channel = $this->createChannel([['1']]);
        $handled = [];

        $this->createAdapter($channel, [true, true, true])->subscribe(
            static function (MessageInterface $message) use (&$handled): bool {
                $handled[] = $message->getPayload();
                return false;
            },
        );

        self::assertSame(['1'], $handled);
        self::assertSame(['cancel consumer', 'nack 1 requeue'], $this->calls);
    }

    public function testRequeuesMessagesDeliveredAfterRefusalWithoutHandlingThem(): void
    {
        $channel = $this->createChannel([['1', '2', '3']]);
        $handled = [];

        $this->createAdapter($channel, [true, true])->subscribe(
            static function (MessageInterface $message) use (&$handled): bool {
                $handled[] = $message->getPayload();
                return $message->getPayload() === '1';
            },
        );

        self::assertSame(['1', '2'], $handled);
        self::assertSame(['ack 1', 'cancel consumer', 'nack 2 requeue', 'nack 3 requeue'], $this->calls);
    }

    public function testRequeuesMessageWhenHandlerReturnsNonBoolean(): void
    {
        $channel = $this->createChannel([['1']]);

        $this->createAdapter($channel, [true, true])->subscribe(static fn(): int => 1);

        self::assertSame(['cancel consumer', 'nack 1 requeue'], $this->calls);
    }

    /**
     * @param list<list<string>> $deliveries Messages delivered by each `wait()` call.
     */
    private function createChannel(array $deliveries): AMQPChannel
    {
        $consumer = null;
        $channel = $this->createMock(AMQPChannel::class);
        $channel->method('basic_consume')->willReturnCallback(
            static function (mixed ...$arguments) use (&$consumer): string {
                $consumer = $arguments[6];
                return 'consumer';
            },
        );
        $channel->method('wait')->willReturnCallback(
            static function () use (&$consumer, &$deliveries): void {
                foreach (array_shift($deliveries) ?? [] as $body) {
                    $message = new AMQPMessage($body);
                    $message->setDeliveryInfo((int) $body, false, '', 'queue');
                    $message->setConsumerTag('consumer');
                    $consumer($message);
                }
            },
        );
        $channel->method('basic_ack')->willReturnCallback(function (int $tag): void {
            $this->calls[] = "ack $tag";
        });
        $channel->method('basic_nack')->willReturnCallback(function (int $tag, bool $multiple, bool $requeue): void {
            $this->calls[] = "nack $tag" . ($requeue ? ' requeue' : '');
        });
        $channel->method('basic_cancel')->willReturnCallback(function (string $tag): mixed {
            $this->calls[] = "cancel $tag";
            return null;
        });

        return $channel;
    }

    /**
     * @param bool[] $canContinue Results of the loop's `canContinue()` calls.
     */
    private function createAdapter(AMQPChannel $channel, array $canContinue): Adapter
    {
        $settings = $this->createMock(QueueSettingsInterface::class);
        $settings->method('getName')->willReturn('queue');
        $settings->method('getQosSettings')->willReturn(null);
        $provider = $this->createMock(QueueProviderInterface::class);
        $provider->method('getChannel')->willReturn($channel);
        $provider->method('getQueueSettings')->willReturn($settings);

        $serializer = $this->createMock(MessageSerializerInterface::class);
        $serializer->method('unserialize')->willReturnCallback(
            static fn(string $body): MessageInterface => new GenericMessage('job', $body),
        );

        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturnOnConsecutiveCalls(...[...$canContinue, false]);

        return new Adapter($provider, $serializer, $loop);
    }
}
