<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use Yiisoft\Yii\Queue\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Yii\Queue\AMQP\Settings\QueueSettingsInterface;

interface QueueProviderInterface
{
    public function getChannel(): AMQPChannel;

    public function getQueueSettings(): QueueSettingsInterface;

    public function getExchangeSettings(): ?ExchangeSettingsInterface;

    public function withChannelName(string $channel): self;
}
