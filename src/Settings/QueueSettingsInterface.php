<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;

interface QueueSettingsInterface
{
    /**
     * @return array|AMQPTable
     */
    public function getArguments();

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
}
