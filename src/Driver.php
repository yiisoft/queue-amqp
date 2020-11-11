<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;

final class Driver implements DriverInterface
{
    protected QueueProviderInterface $queueProvider;
    protected MessageSerializerInterface $serializer;
    protected LoopInterface $loop;

    public function __construct(
        QueueProviderInterface $queueProvider,
        MessageSerializerInterface $serializer,
        LoopInterface $loop
    ) {
        $this->queueProvider = $queueProvider;
        $this->serializer = $serializer;
        $this->loop = $loop;
    }

    /**
     * @inheritDoc
     */
    public function nextMessage(): ?MessageInterface
    {
        $message = null;

        $channel = $this->queueProvider->getChannel();
        $channel->basic_consume(
            $this->queueProvider->getQueueSettings()->getName(),
            '',
            false,
            true,
            false,
            false,
            function (AMQPMessage $amqpMessage) use (&$message): void {
                $message = $this->serializer->unserialize($amqpMessage->body);
            }
        );
        $channel->wait(null, true);

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function status(string $id): JobStatus
    {
        throw new RuntimeException('Status check is not supported by the driver');
    }

    /**
     * @inheritDoc
     */
    public function push(MessageInterface $message): void
    {
        $payload = $this->serializer->serialize($message);
        $amqpMessage = new AMQPMessage($payload);
        $exchange = $this->queueProvider->getExchangeSettings()->getName();
        $this->queueProvider->getChannel()->basic_publish($amqpMessage, $exchange);
    }

    /**
     * @inheritDoc
     */
    public function subscribe(callable $handler): void
    {
        while ($this->loop->canContinue()) {
            $channel = $this->queueProvider->getChannel();
            $channel->basic_consume(
                $this->queueProvider->getQueueSettings()->getName(),
                '',
                false,
                true,
                false,
                false,
                fn (AMQPMessage $amqpMessage) => $handler($this->serializer->unserialize($amqpMessage->body))
            );

            $channel->wait();
        }
    }
}
