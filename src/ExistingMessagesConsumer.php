<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

/**
 * @internal
 */
final class ExistingMessagesConsumer
{
    private string $queueName;
    private AMQPChannel $channel;
    private MessageSerializerInterface $serializer;
    private bool $messageConsumed = false;

    public function __construct(AMQPChannel $channel, string $queueName, MessageSerializerInterface $serializer)
    {
        $this->channel = $channel;
        $this->queueName = $queueName;
        $this->serializer = $serializer;
    }

    public function consume(callable $callback): void
    {
        $this->channel->basic_consume(
            $this->queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $amqpMessage) use ($callback): void {
                try {
                    $message = $this->serializer->unserialize($amqpMessage->body);
                    if ($this->messageConsumed = $callback($message)) {
                        $this->channel->basic_ack($amqpMessage->getDeliveryTag());
                    }
                } catch (Throwable $exception) {
                    $this->messageConsumed = false;
                    $this->channel->basic_cancel($amqpMessage->getConsumerTag());

                    throw $exception;
                }
            }
        );

        do {
            $this->messageConsumed = false;
            $this->channel->wait(null, true);
        } while ($this->messageConsumed === true);
    }
}
