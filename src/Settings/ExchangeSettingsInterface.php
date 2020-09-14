<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Settings;

use PhpAmqpLib\Wire\AMQPTable;

interface ExchangeSettingsInterface
{
    /**
     * @return array|AMQPTable
     */
    public function getArguments();

    public function getName(): string;

    public function getTicket(): ?int;

    public function getType(): string;

    public function isAutoDeletable(): bool;

    public function isDurable(): bool;

    public function isInternal(): bool;

    public function hasNowait(): bool;

    public function isPassive(): bool;

    public function getPositionalSettings(): array;
}
