<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP;

use BackedEnum;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

final class Adapter implements AdapterInterface
{
    public function __construct(
        private QueueProviderInterface $queueProvider,
        private readonly MessageSerializerInterface $serializer,
        private readonly LoopInterface $loop,
    ) {
    }

    public function withChannel(BackedEnum|string $channel): self
    {
        $instance = clone $this;
        $channelName = is_string($channel) ? $channel : (string) $channel->value;
        $instance->queueProvider = $this->queueProvider->withChannelName($channelName);

        return $instance;
    }

    /**
     * @param callable(MessageInterface): bool  $handlerCallback
     */
    public function runExisting(callable $handlerCallback): void
    {
        (new ExistingMessagesConsumer(
            $this->queueProvider->getQueueSettings()->getName(),
            $this->serializer
        ))->consume($handlerCallback, $this->queueProvider->getChannel());
    }

    /**
     * @return never
     */
    public function status(int|string $id): JobStatus
    {
        throw new NotImplementedException('Status check is not supported by the adapter ' . self::class . '.');
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $payload = $this->serializer->serialize($message);
        $amqpMessage = new AMQPMessage(
            $payload,
            array_merge(['message_id' => uniqid(more_entropy: true)], $this->queueProvider->getMessageProperties())
        );
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
        /** @var string $messageId */
        $messageId = $amqpMessage->get('message_id');

        return new IdEnvelope($message, $messageId);
    }

    public function subscribe(callable $handlerCallback): void
    {
        $channel = $this->queueProvider->getChannel();
        $channel->basic_consume(
            $this->queueProvider
                ->getQueueSettings()
                ->getName(),
            $this->queueProvider
                ->getQueueSettings()
                ->getName(),
            false,
            false,
            false,
            true,
            function (AMQPMessage $amqpMessage) use ($handlerCallback, $channel): void {
                try {
                    $handlerCallback($this->serializer->unserialize($amqpMessage->getBody()));
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

        while ($this->loop->canContinue()) {
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

    public function getChannel(): string
    {
        return $this->queueProvider->getQueueSettings()->getName();
    }
}
