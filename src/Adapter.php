<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;

final class Adapter implements AdapterInterface
{
    public function __construct(
        private QueueProviderInterface $queueProvider,
        private MessageSerializerInterface $serializer,
        private LoopInterface $loop,
    ) {
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
        (new ExistingMessagesConsumer($channel, $this->queueProvider
            ->getQueueSettings()
            ->getName(), $this->serializer))
            ->consume($callback);
    }

    public function status(string $id): JobStatus
    {
        throw new NotImplementedException('Status check is not supported by the adapter ' . self::class . '.');
    }

    public function push(MessageInterface $message): void
    {
        $payload = $this->serializer->serialize($message);
        $amqpMessage = new AMQPMessage($payload, $this->queueProvider->getMessageProperties());
        $exchangeSettings = $this->queueProvider->getExchangeSettings();
        $this->queueProvider
            ->getChannel()
            ->basic_publish(
                $amqpMessage,
                $exchangeSettings?->getName() ?? '',
                $exchangeSettings ? '' : $this->queueProvider
                    ->getQueueSettings()
                    ->getName()
            );
    }

    public function subscribe(callable $handler): void
    {
        while ($this->loop->canContinue()) {
            $channel = $this->queueProvider->getChannel();
            $channel->basic_consume(
                $this->queueProvider
                    ->getQueueSettings()
                    ->getName(),
                '',
                false,
                false,
                false,
                false,
                function (AMQPMessage $amqpMessage) use ($handler, $channel): void {
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

    public function getQueueProvider(): QueueProviderInterface
    {
        return $this->queueProvider;
    }

    public function withQueueProvider(QueueProviderInterface $queueProvider): self
    {
        $new = clone $this;
        $new->queueProvider = $queueProvider;

        return $new;
    }
}
