<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;

interface QueueSettingsInterface
{
    public function getArguments(): AMQPTable|array;

    public function getName(): string;

    public function getTicket(): ?int;

    public function isAutoDeletable(): bool;

    public function isDurable(): bool;

    public function isExclusive(): bool;

    public function hasNowait(): bool;

    public function isPassive(): bool;

    public function getPositionalSettings(): array;

    public function withArguments(AMQPTable|array $arguments): self;

    public function withName(string $name): self;

    public function withTicket(?int $ticket): self;

    public function withAutoDeletable(bool $autoDeletable): self;

    public function withDurable(bool $durable): self;

    public function withExclusive(bool $exclusive): self;

    public function withNowait(bool $nowait): self;

    public function withPassive(bool $passive): self;

}
