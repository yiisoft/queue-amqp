<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP;

use InvalidArgumentException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\DelayEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;
use Yiisoft\Queue\MessageStatus;

final class Adapter implements AdapterInterface
{
    private ?AMQPMessage $amqpMessage = null;

    public function __construct(
        private QueueProviderInterface $queueProvider,
        private readonly MessageSerializerInterface $serializer,
        private readonly LoopInterface $loop,
    ) {
    }

    /**
     * @param callable(MessageInterface): bool  $handlerCallback
     */
    public function runExisting(callable $handlerCallback): void
    {
        (new ExistingMessagesConsumer($this->queueProvider, $this->serializer))->consume($handlerCallback);
    }

    public function status(string|int $id): MessageStatus
    {
        return MessageStatus::NOT_FOUND;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $delaySeconds = $message->getMetadata()[DelayEnvelope::META_DELAY_SECONDS] ?? null;
        if ($delaySeconds !== null) {
            $this->pushDelayed($message, $delaySeconds);
            return $message;
        }

        $this->amqpMessage ??= new AMQPMessage(
            '',
            $this->queueProvider->getMessageProperties(),
        );
        $amqpMessage = $this->amqpMessage;

        $payload = $this->serializer->serialize($message);
        $amqpMessage->setBody($payload);
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

        return $message;
    }

    private function pushDelayed(MessageInterface $message, int $delaySeconds): void
    {
        $exchangeSettings = $this->queueProvider->getExchangeSettings();
        if ($exchangeSettings === null) {
            throw new InvalidArgumentException('Message cannot be delayed to a queue without an exchange. Exchange is mandatory.');
        }

        $dlxExchangeSettings = $exchangeSettings
            ->withName("{$exchangeSettings->getName()}.dlx")
            ->withAutoDelete(true)
            ->withType(AMQPExchangeType::TOPIC);

        $deliveryTime = time() + $delaySeconds;
        $delayMilliseconds = $delaySeconds * 1000;
        $queueSettings = $this->queueProvider->getQueueSettings();
        $dlxQueueSettings = $queueSettings
            ->withName("{$queueSettings->getName()}.dlx.$deliveryTime")
            ->withAutoDeletable(true)
            ->withArguments([
                'x-dead-letter-exchange' => ['S', $exchangeSettings->getName()],
                'x-expires' => ['I', $delayMilliseconds + 30000],
                'x-message-ttl' => ['I', $delayMilliseconds],
            ]);

        $messageProperties = array_merge(
            $this->queueProvider->getMessageProperties(),
            [
                'expiration' => $delayMilliseconds,
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ],
        );

        $dlxQueueProvider = $this->queueProvider
            ->withExchangeSettings($dlxExchangeSettings)
            ->withQueueSettings($dlxQueueSettings)
            ->withMessageProperties($messageProperties);

        $amqpMessage = new AMQPMessage(
            $this->serializer->serialize($message),
            $dlxQueueProvider->getMessageProperties(),
        );

        $dlxQueueProvider
            ->getChannel()
            ->basic_publish(
                $amqpMessage,
                $dlxExchangeSettings->getName(),
                '',
            );
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
