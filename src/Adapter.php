<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Adapter\BehaviorChecker;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\Behaviors\ExecutableBehaviorInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;

final class Adapter implements AdapterInterface
{
    private const BEHAVIORS_AVAILABLE = [];
    protected QueueProviderInterface $queueProvider;
    protected MessageSerializerInterface $serializer;
    protected LoopInterface $loop;
    private ?BehaviorChecker $behaviorChecker;

    public function __construct(
        QueueProviderInterface $queueProvider,
        MessageSerializerInterface $serializer,
        LoopInterface $loop,
        ?BehaviorChecker $behaviorChecker = null
    ) {
        $this->queueProvider = $queueProvider;
        $this->serializer = $serializer;
        $this->loop = $loop;
        $this->behaviorChecker = $behaviorChecker;
    }

    public function withChannel(string $channel): self
    {
        $instance = clone $this;
        $instance->queueProvider = $this->queueProvider->withChannelName($channel);

        return $instance;
    }

    public function runExisting(callable $callback): void
    {
        $channel = $this->queueProvider->getChannel();
        (new ExistingMessagesConsumer($channel, $this->queueProvider->getQueueSettings()->getName(), $this->serializer))
            ->consume($callback);
    }

    public function status(string $id): JobStatus
    {
        throw new RuntimeException('Status check is not supported by the adapter');
    }

    public function push(MessageInterface $message): void
    {
        $behaviors = $message->getBehaviors();
        if ($this->behaviorChecker !== null) {
            $this->behaviorChecker->check(self::class, $behaviors, self::BEHAVIORS_AVAILABLE);
        }

        foreach ($behaviors as $behavior) {
            if ($behavior instanceof ExecutableBehaviorInterface) {
                $behavior->execute();
            }
        }

        $payload = $this->serializer->serialize($message);
        $amqpMessage = new AMQPMessage($payload);
        $exchangeSettings = $this->queueProvider->getExchangeSettings();
        $this->queueProvider->getChannel()->basic_publish(
            $amqpMessage,
            $exchangeSettings ? $exchangeSettings->getName() : '',
            $exchangeSettings ? '' : $this->queueProvider->getQueueSettings()->getName()
        );
    }

    public function subscribe(callable $handler): void
    {
        while ($this->loop->canContinue()) {
            $channel = $this->queueProvider->getChannel();
            $channel->basic_consume(
                $this->queueProvider->getQueueSettings()->getName(),
                '',
                false,
                false,
                false,
                false,
                function (AMQPMessage $amqpMessage) use ($handler, $channel): void {
                    if ($amqpMessage->getConsumerTag() === null) {
                        throw new RuntimeException('Amqp message consumer tag cannot be null');
                    }

                    try {
                        $handler($this->serializer->unserialize($amqpMessage->body));
                        $channel->basic_ack($amqpMessage->getDeliveryTag());
                    } catch (Throwable $exception) {
                        $consumerTag = $amqpMessage->getConsumerTag();
                        if ($consumerTag !== null) {
                            $channel->basic_cancel($consumerTag);
                        }

                        throw $exception;
                    }
                }
            );

            $channel->wait();
        }
    }
}
