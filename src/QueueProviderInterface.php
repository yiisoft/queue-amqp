<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\AMQP;

use PhpAmqpLib\Channel\AMQPChannel;
use Yiisoft\Yii\Queue\Driver\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Yii\Queue\Driver\AMQP\Settings\QueueSettingsInterface;

interface QueueProviderInterface
{
    public function getChannel(): AMQPChannel;

    public function getQueueSettings(): QueueSettingsInterface;

    public function getExchangeSettings(): ExchangeSettingsInterface;
}
