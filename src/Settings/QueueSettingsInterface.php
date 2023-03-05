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

    /**
     * Returns positional arguments to be used with {@see \PhpAmqpLib\Channel\AMQPChannel::queue_declare()}
     *
     * @see \Yiisoft\Yii\Queue\AMQP\QueueProvider::getChannel()
     *
     * @return (AMQPTable|array|bool|int|null|string)[]
     *
     * @psalm-return array{0: string, 1: bool, 2: bool, 3: bool, 4: bool, 5: bool, 6: AMQPTable|array, 7: int|null}
     */
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
