<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;

class Driver implements DriverInterface
{
    protected QueueProviderInterface $queueProvider;
    protected SerializerInterface $serializer;
    protected LoopInterface $loop;

    public function __construct(
        QueueProviderInterface $queueProvider,
        SerializerInterface $serializer,
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
                $message = $this->createMessage($amqpMessage);
            }
        );
        $channel->wait(null, true);

        return $message;
    }

    protected function createMessage(AMQPMessage $message): MessageInterface {
        $payload = $this->serializer->unserialize($message->body);

        return new Message($payload['name'], $payload['data'], $payload['meta']);
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
    public function push(MessageInterface $message): ?string
    {
        $payload = [
            'name' => $message->getPayloadName(),
            'data' => $message->getPayloadData(),
            'meta' => $message->getPayloadMeta(),
        ];
        $amqpMessage = new AMQPMessage($this->serializer->serialize($payload));
        $exchange = $this->queueProvider->getExchangeSettings()->getName();
        $this->queueProvider->getChannel()->basic_publish($amqpMessage, $exchange);

        return null;
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
                fn (AMQPMessage $amqpMessage) => $handler($this->createMessage($amqpMessage))
            );

            $channel->wait(null, true);
        }
    }

    /**
     * @inheritDoc
     */
    public function canPush(MessageInterface $message): bool
    {
        $meta = $message->getPayloadMeta();

        return !isset($meta[PayloadInterface::META_KEY_DELAY]) && !isset($meta[PayloadInterface::META_KEY_PRIORITY]);
    }
}
