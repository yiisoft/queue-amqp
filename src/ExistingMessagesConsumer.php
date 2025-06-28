<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

/**
 * @internal
 */
final class ExistingMessagesConsumer
{
    private bool $messageConsumed = false;

    public function __construct(
        private readonly AMQPChannel $channel,
        private readonly string $queueName,
        private readonly MessageSerializerInterface $serializer
    ) {
    }

    /**
     * @param callable(MessageInterface): bool  $callback
     */
    public function consume(callable $callback): void
    {
        $consumerTag = uniqid(more_entropy: true);
        try {
            $this->channel->basic_consume(
                $this->queueName,
                $consumerTag,
                false,
                false,
                false,
                false,
                function (AMQPMessage $amqpMessage) use ($callback): void {
                    try {
                        $message = $this->serializer->unserialize($amqpMessage->getBody());
                        if ($this->messageConsumed = $callback($message)) {
                            $this->channel->basic_ack($amqpMessage->getDeliveryTag());
                        }
                    } catch (Throwable $exception) {
                        $this->messageConsumed = false;
                        $this->channel->basic_nack($amqpMessage->getDeliveryTag(), false, true);

                        throw $exception;
                    }
                }
            );

            do {
                $this->messageConsumed = false;
                $this->channel->wait(null, true);
            } while ($this->messageConsumed === true);
        } finally {
            $this->channel->basic_cancel($consumerTag, false, false);
            $this->channel->close();
        }
    }
}
