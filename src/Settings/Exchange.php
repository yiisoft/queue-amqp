<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Yii\Queue\AMQP\Exception\InvalidArgumentsTypeException;

final class Exchange implements ExchangeSettingsInterface
{
    private \PhpAmqpLib\Wire\AMQPTable|array $arguments;

    /**
     * @param AMQPTable|array $arguments
     */
    public function __construct(
        private string $exchangeName,
        private string $type = AMQPExchangeType::DIRECT,
        private bool $passive = false,
        private bool $durable = false,
        private bool $autoDelete = true,
        private bool $internal = false,
        private bool $nowait = false,
        $arguments = [],
        private ?int $ticket = null
    ) {
        if (!is_array($arguments) && !$arguments instanceof AMQPTable) {
            throw new InvalidArgumentsTypeException();
        }
        $this->arguments = $arguments;
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
