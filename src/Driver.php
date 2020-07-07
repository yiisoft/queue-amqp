<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Job\JobInterface;
use Yiisoft\Yii\Queue\MessageInterface;

class Driver implements DriverInterface
{
    private QueueProviderInterface $queueProvider;
    protected SerializerInterface $serializer;

    public function __construct(QueueProviderInterface $queueProvider, SerializerInterface $serializer)
    {
        $this->queueProvider = $queueProvider;
        $this->serializer = $serializer;
    }

    /**
     * @inheritDoc
     */
    public function nextMessage(): ?MessageInterface
    {
        $message = null;

        $channel = $this->queueProvider->getChannel();
        $channel->basic_consume(
            $this->queueProvider->getQueueName(),
            '',
            false,
            true,
            false,
            false,
            fn (AMQPMessage $amqpMessage) => $message = new Message($this->serializer->unserialize($amqpMessage->body))
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
    public function push(JobInterface $job): MessageInterface
    {
        $amqpMessage = new AMQPMessage($this->serializer->serialize($job));
        $this->queueProvider->getChannel()->basic_publish($amqpMessage, $this->queueProvider->getExchangeName());

        return new Message($job);
    }

    /**
     * @inheritDoc
     */
    public function subscribe(callable $handler): void
    {
        // TODO: Implement subscribe() method.
    }

    /**
     * @inheritDoc
     */
    public function canPush(JobInterface $job): bool
    {
        // TODO: Implement canPush() method.
    }
}
