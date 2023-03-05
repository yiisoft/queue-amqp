<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;

interface ExchangeSettingsInterface
{
    public function getArguments(): AMQPTable|array;

    public function getName(): string;

    public function getTicket(): ?int;

    public function getType(): string;

    public function isAutoDeletable(): bool;

    public function isDurable(): bool;

    public function isInternal(): bool;

    public function hasNowait(): bool;

    public function isPassive(): bool;

    /**
     * Positional arguments to be used with {@see \PhpAmqpLib\Channel\AMQPChannel::exchange_declare()}
     *
     * @see \Yiisoft\Yii\Queue\AMQP\QueueProvider::getChannel()
     *
     * @return (AMQPTable|array|bool|int|null|string)[]
     *
     * @psalm-return array{string, string, bool, bool, bool, bool, bool, AMQPTable|array, int|null}
     */
    public function getPositionalSettings(): array;

    public function withArguments(AMQPTable|array $arguments): self;

    public function withName(string $name): self;

    public function withTicket(?int $ticket): self;

    public function withType(string $type): self;

    public function withAutoDeletable(bool $autoDelete): self;

    public function withDurable(bool $durable): self;

    public function withInternal(bool $internal): self;

    public function withNowait(bool $nowait): self;

    public function withPassive(bool $passive): self;
}
