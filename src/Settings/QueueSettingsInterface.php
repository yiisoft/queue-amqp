<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;

interface QueueSettingsInterface
{
    /**
     * @return AMQPTable|array
     */
    public function getArguments(): AMQPTable|array;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return int|null
     */
    public function getTicket(): ?int;

    /**
     * @return bool
     */
    public function isAutoDeletable(): bool;

    /**
     * @return bool
     */
    public function isDurable(): bool;

    /**
     * @return bool
     */
    public function isExclusive(): bool;

    /**
     * @return bool
     */
    public function hasNowait(): bool;

    /**
     * @return bool
     */
    public function isPassive(): bool;

    public function getPositionalSettings(): array;

    public function withName(string $name): self;
}
