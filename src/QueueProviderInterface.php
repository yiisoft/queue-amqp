<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\Interop;

use PhpAmqpLib\Channel\AMQPChannel;

interface QueueProviderInterface
{
    public function getChannel(): AMQPChannel;

    public function getQueueName(): string;

    public function getExchangeName(): string;
}
