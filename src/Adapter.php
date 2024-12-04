<?php

declare(strict_types=1);

namespace App;

use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Queue\AMQP\ExistingMessagesConsumer;
use Yiisoft\Queue\AMQP\MessageSerializerInterface;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;

final class Adapter implements AdapterInterface {
    /**
     * @param QueueProviderInterface $queueProvider
     * @param MessageSerializerInterface $serializer
     * @param LoopInterface $loop
     */
    public function __construct(
        private QueueProviderInterface              $queueProvider,
        private readonly MessageSerializerInterface $serializer,
        private readonly LoopInterface              $loop,
    ) {
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function withChannel(string $channel): self {
        $instance = clone $this;
        $instance->queueProvider = $this->queueProvider->withChannelName($channel);

        return $instance;
    }

    /**
     * @param callable(MessageInterface): bool $handlerCallback
     */
    public function runExisting(callable $handlerCallback): void {
        $channel = $this->queueProvider->getChannel();
        (new ExistingMessagesConsumer($channel, $this->queueProvider
            ->getQueueSettings()
            ->getName(), $this->serializer))
            ->consume($handlerCallback);
    }

    /**
     * @param string|int $id
     * @return JobStatus
     */
    public function status(string|int $id): JobStatus {
        throw new NotImplementedException('Status check is not supported by the adapter ' . self::class . '.');
    }

    /**
     * @param MessageInterface $message
     * @return MessageInterface
     */
    public function push(MessageInterface $message): MessageInterface {
        $payload = $this->serializer->serialize($message);
        $amqpMessage = new AMQPMessage(
            $payload,
            array_merge(['message_id' => uniqid('', true)], $this->queueProvider->getMessageProperties())
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
        $message->setId($messageId);

        return $message;
    }

    /**
     * @param callable $handlerCallback
     * @return void
     */
    public function subscribe(callable $handlerCallback): void {
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
                    $handlerCallback($this->serializer->unserialize($amqpMessage->body));
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

    /**
     * @return QueueProviderInterface
     */
    public function getQueueProvider(): QueueProviderInterface {
        return $this->queueProvider;
    }

    /**
     * @param QueueProviderInterface $queueProvider
     * @return $this
     */
    public function withQueueProvider(QueueProviderInterface $queueProvider): self {
        $new = clone $this;
        $new->queueProvider = $queueProvider;

        return $new;
    }
}
