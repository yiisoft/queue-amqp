<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\MessageSerializerInterface;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\QueueProviderInterface;

return [
    MessageSerializerInterface::class => MessageSerializer::class,
    QueueProviderInterface::class => QueueProvider::class,
];
