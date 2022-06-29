<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Yii\Queue\AMQP\Exception\InvalidArgumentsTypeException;
use Yiisoft\Yii\Queue\QueueFactory;

final class Queue implements QueueSettingsInterface
{
    private string $queueName;
    private bool $passive;
    private bool $durable;
    private bool $exclusive;
    private bool $autoDelete;
    private bool $nowait;
    /**
     * @var AMQPTable|array
     */
    private $arguments;
    private ?int $ticket;

    /**
     * @param string $queueName
     * @param bool $passive
     * @param bool $durable
     * @param bool $exclusive
     * @param bool $autoDelete
     * @param bool $nowait
     * @param AMQPTable|array $arguments
     * @param int|null $ticket
     */
    public function __construct(
        string $queueName = QueueFactory::DEFAULT_CHANNEL_NAME,
        bool $passive = false,
        bool $durable = false,
        bool $exclusive = false,
        bool $autoDelete = true,
        bool $nowait = false,
        $arguments = [],
        ?int $ticket = null
    ) {
        if (!is_array($arguments) && !$arguments instanceof AMQPTable) {
            throw new InvalidArgumentsTypeException();
        }

        $this->queueName = $queueName;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->exclusive = $exclusive;
        $this->autoDelete = $autoDelete;
        $this->nowait = $nowait;
        $this->arguments = $arguments;
        $this->ticket = $ticket;
    }

    public function getArguments()
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
