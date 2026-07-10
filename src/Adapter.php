<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp;

use BackedEnum;
use InvalidArgumentException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Amqp\Exception\NotImplementedException;
use Yiisoft\Queue\Amqp\Settings\ExchangeSettingsInterface;
use Yiisoft\Queue\Amqp\Settings\QueueSettingsInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\DelayEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\Serializer\MessageSerializerInterface;
use Yiisoft\Queue\MessageStatus;

use function is_string;

final class Adapter implements AdapterInterface
{
    public function __construct(
        private QueueProviderInterface $queueProvider,
        private readonly MessageSerializerInterface $serializer,
        private readonly LoopInterface $loop,
    ) {}

    public function withChannel(BackedEnum|string $channel): self
    {
        $instance = clone $this;

        $queueName = is_string($channel) ? $channel : (string) $channel->value;
        $instance->queueProvider = $this->queueProvider->withQueueName($queueName);

        return $instance;
    }

    /**
     * @param callable(MessageInterface): bool  $handlerCallback
     */
    public function runExisting(callable $handlerCallback): void
    {
        (new ExistingMessagesConsumer($this->queueProvider, $this->serializer))->consume($handlerCallback);
    }

    /**
     * @return never
     */
    public function status(int|string $id): MessageStatus
    {
        throw new NotImplementedException('Status check is not supported by the adapter ' . self::class . '.');
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $queueProvider = $this->getQueueProviderForMessage($message);

        $amqpMessage = new AMQPMessage(
            '',
            $queueProvider->getMessageProperties(),
        );

        $payload = $this->serializer->serialize($message);
        $amqpMessage->setBody($payload);
        $exchangeSettings = $queueProvider->getExchangeSettings();

        $queueProvider
            ->getChannel()
            ->basic_publish(
                $amqpMessage,
                $exchangeSettings?->getName() ?? '',
                $exchangeSettings ? '' : $queueProvider
                    ->getQueueSettings()
                    ->getName(),
            );

        return $message;
    }

    public function subscribe(callable $handlerCallback): void
    {
        $channel = $this->queueProvider->getChannel();
        $qosSettings = $this->queueProvider
            ->getQueueSettings()
            ->getQosSettings();
        if ($qosSettings !== null) {
            $channel->basic_qos(
                $qosSettings->getPrefetchSize(),
                $qosSettings->getPrefetchCount(),
                $qosSettings->isGlobal(),
            );
        }

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
            },
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

    private function getQueueProviderForMessage(MessageInterface $message): QueueProviderInterface
    {
        $delaySeconds = DelayEnvelope::fromMessage($message)->getDelaySeconds();
        if ($delaySeconds <= 0) {
            return $this->queueProvider;
        }

        $exchangeSettings = $this->queueProvider->getExchangeSettings();
        if ($exchangeSettings === null) {
            throw new InvalidArgumentException('Message cannot be delayed to a queue without an exchange. Exchange is mandatory.');
        }

        $delayMilliseconds = (int) ceil($delaySeconds * 1000);

        return $this->queueProvider
            ->withMessageProperties($this->getDelayMessageProperties($delayMilliseconds))
            ->withExchangeSettings($this->getDelayExchangeSettings($exchangeSettings))
            ->withQueueSettings(
                $this->getDelayQueueSettings(
                    $this->queueProvider->getQueueSettings(),
                    $exchangeSettings,
                    $delayMilliseconds,
                ),
            );
    }

    /**
     * @psalm-return array{expiration: string, delivery_mode: int}&array
     */
    private function getDelayMessageProperties(int $delayMilliseconds): array
    {
        return array_merge(
            $this->queueProvider->getMessageProperties(),
            [
                'expiration' => (string) $delayMilliseconds,
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ],
        );
    }

    private function getDelayQueueSettings(
        QueueSettingsInterface $queueSettings,
        ExchangeSettingsInterface $exchangeSettings,
        int $delayMilliseconds,
    ): QueueSettingsInterface {
        $deliveryTime = time() + (int) ceil($delayMilliseconds / 1000);

        return $queueSettings
            ->withName("{$queueSettings->getName()}.dlx.$deliveryTime")
            ->withAutoDeletable(true)
            ->withArguments(
                [
                    'x-dead-letter-exchange' => ['S', $exchangeSettings->getName()],
                    'x-expires' => ['I', $delayMilliseconds + 30000],
                    'x-message-ttl' => ['I', $delayMilliseconds],
                ],
            );
    }

    private function getDelayExchangeSettings(ExchangeSettingsInterface $exchangeSettings): ExchangeSettingsInterface
    {
        return $exchangeSettings
            ->withName("{$exchangeSettings->getName()}.dlx")
            ->withAutoDelete(true)
            ->withType(AMQPExchangeType::TOPIC);
    }
}
