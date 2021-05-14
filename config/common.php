<?php

declare(strict_types=1);

use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\MessageSerializerInterface;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue;
use Yiisoft\Yii\Queue\AMQP\Settings\QueueSettingsInterface;

return [
    MessageSerializerInterface::class => MessageSerializer::class,
    QueueProviderInterface::class => QueueProvider::class,
    QueueSettingsInterface::class => [
        'class' => Queue::class,
        '__constructor()' => ['queueName' => 'yii-queue'],
    ],
];
