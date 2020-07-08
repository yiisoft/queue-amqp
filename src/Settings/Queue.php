<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\AMQP\Settings;

use InvalidArgumentException;
use PhpAmqpLib\Wire\AMQPTable;

final class Queue implements QueueSettingsInterface
{
    private string $queueName;
    private bool $passive;
    private bool $durable;
    private bool $exclusive;
    private bool $autoDelete;
    private bool $nowait;
    /**
     * @var array|AMQPTable
     */
    private $arguments;
    private ?int $ticket;

    public function __construct(
        string $queueName,
        bool $passive = false,
        bool $durable = false,
        bool $exclusive = false,
        bool $autoDelete = true,
        bool $nowait = false,
        $arguments = [],
        ?int $ticket = null
    ) {
        if (!is_array($arguments) && !$arguments instanceof AMQPTable) {
            $message = '"arguments" parameter must be either an array or an ' . AMQPTable::class . ' object';

            throw new InvalidArgumentException($message);
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
}
