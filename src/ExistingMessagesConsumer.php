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
        private readonly QueueProviderInterface $queueProvider,
        private readonly MessageSerializerInterface $serializer
    ) {
    }

    /**
     * @param callable(MessageInterface): bool  $callback
     */
    public function consume(callable $callback): void
    {
        $channel = $this->queueProvider->getChannel();
        $consumerTag = uniqid(more_entropy: true);
        try {
            $channel->basic_consume(
                $this->queueProvider->getQueueSettings()->getName(),
                $consumerTag,
                false,
                false,
                false,
                false,
                function (AMQPMessage $amqpMessage) use ($callback, $channel): void {
                    try {
                        $message = $this->serializer->unserialize($amqpMessage->getBody());
                        if ($this->messageConsumed = $callback($message)) {
                            $channel->basic_ack($amqpMessage->getDeliveryTag());
                        } else {
                            $channel->basic_nack($amqpMessage->getDeliveryTag(), false, true);
                        }
                    } catch (Throwable $exception) {
                        $this->messageConsumed = false;
                        $channel->basic_nack($amqpMessage->getDeliveryTag(), false, true);

                        throw $exception;
                    }
                }
            );

            do {
                $this->messageConsumed = false;
                $channel->wait(null, true);
            } while ($this->messageConsumed === true);
        } finally {
            $channel->basic_cancel($consumerTag);
            $this->queueProvider->channelClose();
        }
    }
}
