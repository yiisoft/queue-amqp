<?php

declare(strict_types=1);

use Yiisoft\Queue\Amqp\QueueProvider;
use Yiisoft\Queue\Amqp\QueueProviderInterface;
use Yiisoft\Queue\Amqp\Settings\Queue;
use Yiisoft\Queue\Amqp\Settings\QueueSettingsInterface;

return [
    QueueProviderInterface::class => QueueProvider::class,
    QueueSettingsInterface::class => Queue::class,
];
