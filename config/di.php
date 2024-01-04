<?php

declare(strict_types=1);

use Yiisoft\Queue\AMQP\MessageSerializer;
use Yiisoft\Queue\AMQP\MessageSerializerInterface;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\AMQP\Settings\Queue;
use Yiisoft\Queue\AMQP\Settings\QueueSettingsInterface;

return [
    MessageSerializerInterface::class => MessageSerializer::class,
    QueueProviderInterface::class => QueueProvider::class,
    QueueSettingsInterface::class => Queue::class,
];
