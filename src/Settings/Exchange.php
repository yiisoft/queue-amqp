<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use InvalidArgumentException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;

final class Exchange implements ExchangeSettingsInterface
{
    private string $exchangeName;
    private bool $passive;
    private bool $durable;
    private bool $internal;
    private bool $autoDelete;
    private bool $nowait;
    /**
     * @var array|AMQPTable
     */
    private $arguments;
    private ?int $ticket;
    private string $type;

    public function __construct(
        string $exchangeName,
        string $type = AMQPExchangeType::DIRECT,
        bool $passive = false,
        bool $durable = false,
        bool $autoDelete = true,
        bool $internal = false,
        bool $nowait = false,
        $arguments = [],
        ?int $ticket = null
    ) {
        if (!is_array($arguments) && !$arguments instanceof AMQPTable) {
            $message = '"arguments" parameter must be either an array or an ' . AMQPTable::class . ' object';

            throw new InvalidArgumentException($message);
        }

        $this->exchangeName = $exchangeName;
        $this->type = $type;
        $this->passive = $passive;
        $this->durable = $durable;
        $this->autoDelete = $autoDelete;
        $this->internal = $internal;
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
        return $this->exchangeName;
    }

    public function getTicket(): ?int
    {
        return $this->ticket;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isAutoDeletable(): bool
    {
        return $this->autoDelete;
    }

    public function isDurable(): bool
    {
        return $this->durable;
    }

    public function isInternal(): bool
    {
        return $this->internal;
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
            $this->exchangeName,
            $this->type,
            $this->passive,
            $this->durable,
            $this->autoDelete,
            $this->internal,
            $this->nowait,
            $this->arguments,
            $this->ticket,
        ];
    }
}
