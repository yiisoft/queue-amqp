<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\AMQP;

use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

final class QueueProvider implements QueueProviderInterface
{
    private AbstractConnection $connection;
    private array $queueSettings;
    private array $exchangeSettings;
    private AMQPChannel $channel;

    public function __construct(
        AbstractConnection $connection,
        array $queueSettings,
        array $exchangeSettings
    ) {
        $this->connection = $connection;
        $this->queueSettings = $queueSettings;
        $this->exchangeSettings = $exchangeSettings;
    }

    public function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $queueSettings = $this->getQueueSettings();
            $exchangeSettings = $this->getExchangeSettings();

            $this->channel = $this->connection->channel();
            $this->channel->queue_declare(...$queueSettings);
            $this->channel->exchange_declare(...$exchangeSettings);
            $this->channel->queue_bind($queueSettings[0], $exchangeSettings[0]);
        }

        return $this->channel;
    }

    private function getQueueSettings(): array
    {
        $queueName = $this->queueSettings['queueName'] ?? '';
        $passive = $this->queueSettings['passive'] ?? false;
        $durable = $this->queueSettings['durable'] ?? false;
        $exclusive = $this->queueSettings['exclusive'] ?? false;
        $autoDelete = $this->queueSettings['autoDelete'] ?? true;
        $nowait = $this->queueSettings['nowait'] ?? false;
        $arguments = $this->queueSettings['arguments'] ?? [];
        $ticket = $this->queueSettings['ticket'] ?? null;

        return [
            $queueName,
            $passive,
            $durable,
            $exclusive,
            $autoDelete,
            $nowait,
            $arguments,
            $ticket,
        ];
    }

    private function getExchangeSettings(): array
    {
        $exchangeName = $this->exchangeSettings['exchangeName'] ?? null;
        if ($exchangeName === null) {
            throw new InvalidArgumentException('You must provide exchange name to configure AMQP exchange.');
        }

        $type = $this->exchangeSettings['type'] ?? AMQPExchangeType::DIRECT;
        $passive = $this->exchangeSettings['passive'] ?? false;
        $durable = $this->exchangeSettings['durable'] ?? false;
        $autoDelete = $this->exchangeSettings['autoDelete'] ?? true;
        $internal = $this->exchangeSettings['internal'] ?? false;
        $nowait = $this->exchangeSettings['nowait'] ?? false;
        $arguments = $this->exchangeSettings['arguments'] ?? [];
        $ticket = $this->exchangeSettings['ticket'] ?? null;

        return [
            $exchangeName,
            $type,
            $passive,
            $durable,
            $internal,
            $autoDelete,
            $nowait,
            $arguments,
            $ticket,
        ];
    }

    public function getQueueName(): string
    {
        return $this->getQueueSettings()[0];
    }

    public function getExchangeName(): string
    {
        return $this->getExchangeSettings()[0];
    }
}
