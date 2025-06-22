<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Queue\QueueInterface;

final class Queue implements QueueSettingsInterface
{
    public function __construct(
        private string $queueName = QueueInterface::DEFAULT_CHANNEL,
        private bool $passive = false,
        private bool $durable = false,
        private bool $exclusive = false,
        private bool $autoDelete = false,
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

    /**
     * @psalm-return array{0: string, 1: bool, 2: bool, 3: bool, 4: bool, 5: bool, 6: AMQPTable|array, 7: int|null}
     *
     * @psalm-suppress LessSpecificImplementedReturnType Can be removed after raise Psalm version to ^5.0
     */
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

    public function withName(string $name): self
    {
        $instance = clone $this;
        $instance->queueName = $name;

        return $instance;
    }

    public function withArguments(AMQPTable|array $arguments): self
    {
        $new = clone $this;
        $new->arguments = $arguments;

        return $new;
    }

    public function withTicket(?int $ticket): self
    {
        $new = clone $this;
        $new->ticket = $ticket;

        return $new;
    }

    public function withAutoDeletable(bool $autoDeletable): self
    {
        $new = clone $this;
        $new->autoDelete = $autoDeletable;

        return $new;
    }

    public function withDurable(bool $durable): self
    {
        $new = clone $this;
        $new->durable = $durable;

        return $new;
    }

    public function withExclusive(bool $exclusive): self
    {
        $new = clone $this;
        $new->exclusive = $exclusive;

        return $new;
    }

    public function withNowait(bool $nowait): self
    {
        $new = clone $this;
        $new->nowait = $nowait;

        return $new;
    }

    public function withPassive(bool $passive): self
    {
        $new = clone $this;
        $new->passive = $passive;

        return $new;
    }
}
