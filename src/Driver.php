<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
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
        $data = $this->queueProvider->getChannel()->basic_consume(
            $this->queueProvider->getQueueName(),
            '',
            false,
            true
        );

        return new Message($this->serializer->unserialize($data));
    }

    /**
     * @inheritDoc
     */
    public function status(string $id): JobStatus
    {
        // TODO: Implement status() method.
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
