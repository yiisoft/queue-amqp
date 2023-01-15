<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use Yiisoft\Yii\Queue\AMQP\Settings\Exchange;
use Yiisoft\Yii\Queue\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Yii\Queue\AMQP\Settings\QueueSettingsInterface;

final class QueueProvider implements QueueProviderInterface
{
    public const EXCHANGE_NAME_DEFAULT = 'yii-queue';

    private ?AMQPChannel $channel = null;

    public function __construct(
        private AbstractConnection $connection,
        private QueueSettingsInterface $queueSettings,
        private ?ExchangeSettingsInterface $exchangeSettings = null,
        private array $messageProperties = [],
    ) {
        if ($this->exchangeSettings === null) {
            $this->exchangeSettings = new Exchange(self::EXCHANGE_NAME_DEFAULT);
        }
    }

    public function __destruct()
    {
        $this->channel?->close();
    }

    public function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = $this->connection->channel();
            $this->channel->queue_declare(...$this->queueSettings->getPositionalSettings());

            if ($this->exchangeSettings !== null) {
                $this->channel->exchange_declare(...$this->exchangeSettings->getPositionalSettings());
                $this->channel->queue_bind($this->queueSettings->getName(), $this->exchangeSettings->getName());
            }
        }

        return $this->channel;
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
        $instance->channel = null;
        $instance->queueSettings = $instance->queueSettings->withName($channel);

        return $instance;
    }

    public function withQueueSettings(QueueSettingsInterface $queueSettings): QueueProviderInterface
    {
        $new = clone $this;
        $new->queueSettings = $queueSettings;

        return $new;
    }

    public function withExchangeSettings(?ExchangeSettingsInterface $exchangeSettings): QueueProviderInterface
    {
        $new = clone $this;
        $new->exchangeSettings = $exchangeSettings;

        return $new;
    }

    public function withMessageProperties(array $properties): QueueProviderInterface
    {
        $new = clone $this;
        $new->messageProperties = $properties;

        return $new;
    }
}
