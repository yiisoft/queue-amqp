<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Yiisoft\Injector\Injector;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\QueueFactory;

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

    public function testDifferentChannel(): void
    {
        $this->queueName = 'yii-queue-channel2';
        $fileHelper = new FileHelper();

        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings()
        );

        $this->queueProvider = $queueProvider->withExchangeSettings(null);

        $container = $this->getContainer();
        $adapter = $this->getAdapter();

        $factory = new QueueFactory(
            [
                'channel1' => $adapter,
                'channel2' => $adapter->withChannel($this->queueName),
            ],
            $this->getQueue(),
            $container,
            new CallableFactory($container),
            new Injector($container)
        );

        $time = time();
        $queue = $factory->get('channel2');
        $queue->push(new Message(['file_name' => 'test-channel-run', 'payload' => ['time' => $time]]));

        self::assertNull($fileHelper->get('test-channel-run'));

        $queue->run();

        $result = $fileHelper->get('test-channel-run');
        self::assertNotNull($result);
        self::assertEquals($time, $result);
    }
}
