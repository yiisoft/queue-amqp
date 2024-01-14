<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP;

use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

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
        $new = clone $this;
        $new->queueProvider = $this->queueProvider->withChannelName($channel);

        return $new;
    }

    /**
     * @param callable(MessageInterface): bool $handlerCallback
     */
    public function runExisting(callable $handlerCallback): void
    {
        $channel = $this->queueProvider->getChannel();
        $queueName = $this->queueProvider->getQueueSettings()->getName();
        $consumer = new ExistingMessagesConsumer(
            $channel,
            $queueName,
            $this->serializer
        );

        $consumer->consume($handlerCallback);
    }

    public function status(string|int $id): JobStatus
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
        $channel = $this->queueProvider->getChannel();
        $channel->basic_publish(
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
        $queueName = $this->queueProvider->getQueueSettings()->getName();

        $channel->basic_consume(
            $queueName,
            $queueName,
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
