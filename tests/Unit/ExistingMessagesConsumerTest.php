<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Unit;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Amqp\ExistingMessagesConsumer;
use Yiisoft\Queue\Amqp\QueueProviderInterface;
use Yiisoft\Queue\Amqp\Settings\QueueSettingsInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\Serializer\MessageSerializerInterface;

final class ExistingMessagesConsumerTest extends TestCase
{
    /**
     * @var string[]
     */
    private array $calls = [];

    public function testAcknowledgesHandledMessagesUntilQueueIsEmpty(): void
    {
        $this->createConsumer([['1'], ['2'], []])->consume(static fn(): bool => true);

        self::assertSame(['ack 1', 'ack 2', 'cancel', 'close'], $this->calls);
    }

    public function testRequeuesMessageWhenHandlerReturnsNonBoolean(): void
    {
        $this->createConsumer([['1'], ['2']])->consume(static fn(): int => 1);

        self::assertSame(['nack 1 requeue', 'cancel', 'close'], $this->calls);
    }

    /**
     * @param list<list<string>> $deliveries Messages delivered by each `wait()` call.
     */
    private function createConsumer(array $deliveries): ExistingMessagesConsumer
    {
        $consumer = null;
        $channel = $this->createMock(AMQPChannel::class);
        $channel->method('basic_consume')->willReturnCallback(
            static function (mixed ...$arguments) use (&$consumer): string {
                $consumer = $arguments[6];
                return (string) $arguments[1];
            },
        );
        $channel->method('wait')->willReturnCallback(
            static function () use (&$consumer, &$deliveries): void {
                foreach (array_shift($deliveries) ?? [] as $body) {
                    $message = new AMQPMessage($body);
                    $message->setDeliveryInfo((int) $body, false, '', 'queue');
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
        $channel->method('basic_cancel')->willReturnCallback(function (): mixed {
            $this->calls[] = 'cancel';
            return null;
        });

        $settings = $this->createMock(QueueSettingsInterface::class);
        $settings->method('getName')->willReturn('queue');
        $provider = $this->createMock(QueueProviderInterface::class);
        $provider->method('getChannel')->willReturn($channel);
        $provider->method('getQueueSettings')->willReturn($settings);
        $provider->method('channelClose')->willReturnCallback(function (): void {
            $this->calls[] = 'close';
        });

        $serializer = $this->createMock(MessageSerializerInterface::class);
        $serializer->method('unserialize')->willReturnCallback(
            static fn(string $body): MessageInterface => new GenericMessage('job', $body),
        );

        return new ExistingMessagesConsumer($provider, $serializer);
    }
}
