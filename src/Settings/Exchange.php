<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Settings;

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;

final class Exchange implements ExchangeSettingsInterface
{
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

    public function isAutoDelete(): bool
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

    /**
     * @return self
     */
    public function withArguments(AMQPTable|array $arguments): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->arguments = $arguments;

        return $new;
    }

    /**
     * @return self
     */
    public function withName(string $name): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->exchangeName = $name;

        return $new;
    }

    /**
     * @return self
     */
    public function withTicket(?int $ticket): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->ticket = $ticket;

        return $new;
    }

    /**
     * @return self
     */
    public function withType(string $type): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->type = $type;

        return $new;
    }

    /**
     * @return self
     */
    public function withAutoDelete(bool $autoDelete): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->autoDelete = $autoDelete;

        return $new;
    }

    /**
     * @return self
     */
    public function withDurable(bool $durable): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->durable = $durable;

        return $new;
    }

    /**
     * @return self
     */
    public function withInternal(bool $internal): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->internal = $internal;

        return $new;
    }

    /**
     * @return self
     */
    public function withNowait(bool $nowait): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->nowait = $nowait;

        return $new;
    }

    /**
     * @return self
     */
    public function withPassive(bool $passive): ExchangeSettingsInterface
    {
        $new = clone $this;
        $new->passive = $passive;

        return $new;
    }
}
