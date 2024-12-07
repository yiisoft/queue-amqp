<?php

declare(strict_types=1);

use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\AMQP\Settings\Queue;
use Yiisoft\Queue\AMQP\Settings\QueueSettingsInterface;

return [
    QueueProviderInterface::class => QueueProvider::class,
    QueueSettingsInterface::class => Queue::class,
];
