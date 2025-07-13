<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Yiisoft\Queue\AMQP\Exception\ExchangeDeclaredException;
use Yiisoft\Queue\AMQP\Settings\Exchange;
use Yiisoft\Queue\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Queue\AMQP\Settings\QueueSettingsInterface;

/**
 * @internal
 */
final class QueueProvider implements QueueProviderInterface
{
    public const EXCHANGE_NAME_DEFAULT = 'yii-queue';

    private ?int $channelId = null;

    public function __construct(
        private readonly AbstractConnection $connection,
        private QueueSettingsInterface $queueSettings,
        private ?ExchangeSettingsInterface $exchangeSettings = null,
        private array $messageProperties = [],
    ) {
    }

    public function __clone()
    {
        $this->channelId = null;
    }

    public function __destruct()
    {
        if ($this->channelId !== null) {
            $this->connection->channel($this->channelId)->close();
        }
    }

    /**
     * Returns an AMQPChannel instance.
     * IMPORTANT: Do NOT memorise the channel instance, as this will cause memory leaks on channel close!
     */
    public function getChannel(): AMQPChannel
    {
        if ($this->channelId !== null) {
            return $this->connection->channel($this->channelId);
        }

        $this->channelId = $this->connection->get_free_channel_id();
        $channel = $this->connection->channel($this->getChannelId());
        $channel->queue_declare(...$this->queueSettings->getPositionalSettings());

        if ($this->exchangeSettings !== null) {
            $channel->exchange_declare(...$this->exchangeSettings->getPositionalSettings());
            $channel->queue_bind($this->queueSettings->getName(), $this->exchangeSettings->getName());
        }

        return $channel;
    }

    public function getQueueSettings(): QueueSettingsInterface
    {
        return $this->queueSettings;
    }

    public function getExchangeSettings(): ?ExchangeSettingsInterface
    {
        return $this->exchangeSettings;
    }

    public function getMessageProperties(): array
    {
        return $this->messageProperties;
    }

    public function withChannelName(string $channel): self
    {
        if ($channel === $this->queueSettings->getName()) {
            return $this;
        }

        if ($this->exchangeSettings !== null) {
            throw new ExchangeDeclaredException();
        }

        $instance = clone $this;
        $instance->queueSettings = $instance->queueSettings->withName($channel);
        if ($this->channelId !== null) {
            $instance->channelId = null;
        }

        return $instance;
    }

    /**
     * @return self
     */
    public function withQueueSettings(QueueSettingsInterface $queueSettings): QueueProviderInterface
    {
        $new = clone $this;
        $new->queueSettings = $queueSettings;

        return $new;
    }

    /**
     * @return self
     */
    public function withExchangeSettings(?ExchangeSettingsInterface $exchangeSettings): QueueProviderInterface
    {
        $new = clone $this;
        $new->exchangeSettings = $exchangeSettings;

        return $new;
    }

    /**
     * @return self
     */
    public function withMessageProperties(array $properties): QueueProviderInterface
    {
        $new = clone $this;
        $new->messageProperties = $properties;

        return $new;
    }

    public function channelClose(): void
    {
        if ($this->channelId !== null) {
            $this->connection->channel($this->channelId)->close();
            $this->channelId = null;
        }
    }

    private function getChannelId(): int
    {
        if ($this->channelId === null) {
            $this->channelId = $this->connection->get_free_channel_id();
        }

        return $this->channelId;
    }
}
