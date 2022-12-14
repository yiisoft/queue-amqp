<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Yii\Queue\QueueFactoryInterface;

final class Queue implements QueueSettingsInterface
{
    /**
     * @param string $queueName
     * @param bool $passive
     * @param bool $durable
     * @param bool $exclusive
     * @param bool $autoDelete
     * @param bool $nowait
     * @param array|AMQPTable $arguments
     * @param int|null $ticket
     */
    public function __construct(
        private string $queueName = QueueFactoryInterface::DEFAULT_CHANNEL_NAME,
        private bool $passive = false,
        private bool $durable = false,
        private bool $exclusive = false,
        private bool $autoDelete = true,
        private bool $nowait = false,
        private AMQPTable|array $arguments = [],
        private ?int $ticket = null
    ) {
    }

    public function getArguments(): AMQPTable|array
    {
        return $this->arguments;
    }

    public function getName(): string
    {
        return $this->queueName;
    }

    public function getTicket(): ?int
    {
        return $this->ticket;
    }

    public function isAutoDeletable(): bool
    {
        return $this->autoDelete;
    }

    public function isDurable(): bool
    {
        return $this->durable;
    }

    public function isExclusive(): bool
    {
        return $this->exclusive;
    }

    public function hasNowait(): bool
    {
        return $this->nowait;
    }

    public function isPassive(): bool
    {
        return $this->passive;
    }

    public function getPositionalSettings(): array
    {
        return [
            $this->queueName,
            $this->passive,
            $this->durable,
            $this->exclusive,
            $this->autoDelete,
            $this->nowait,
            $this->arguments,
            $this->ticket,
        ];
    }

    public function withName(string $name): QueueSettingsInterface
    {
        $instance = clone $this;
        $instance->queueName = $name;

        return $instance;
    }
}
