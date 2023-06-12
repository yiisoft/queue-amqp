<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\QueueFactory;

class QueueFactoryTest extends UnitTestCase
{
    public function testSameChannelName(): void
    {
        $queue = $this->getQueue();
        $container = $this->getContainer();
        $factory = new QueueFactory(
            [],
            $queue,
            $container,
            new CallableFactory($container),
            new Injector($container),
            true,
            $this->getAdapter()
        );

        self::assertEquals('yii-queue', $factory->get('yii-queue')->getChannelName());
    }
}
