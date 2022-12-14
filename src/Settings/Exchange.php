<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;

final class Exchange implements ExchangeSettingsInterface
{
    /**
     * @param string $exchangeName
     * @param string $type
     * @param bool $passive
     * @param bool $durable
     * @param bool $autoDelete
     * @param bool $internal
     * @param bool $nowait
     * @param AMQPTable|array $arguments
     * @param int|null $ticket
     */
    public function __construct(
        private string $exchangeName,
        private string $type = AMQPExchangeType::DIRECT,
        private bool $passive = false,
        private bool $durable = false,
        private bool $autoDelete = true,
        private bool $internal = false,
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
