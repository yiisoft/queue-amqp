<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Integration\TestCase;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class QueueTest extends TestCase
{
    public function testStatus(): void
    {
        $adapter = new Adapter(
            new QueueProvider(
                $this->connection,
                new QueueSettings(),
            ),
            new MessageSerializer(),
            new SignalLoop(),
        );
        $queue = new Queue(
            $this->createMock(WorkerInterface::class),
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            new PushMiddlewareDispatcher(
                new MiddlewareFactoryPush(
                    $this->createMock(ContainerInterface::class),
                    new CallableFactory($this->createMock(ContainerInterface::class)),
                ),
            ),
            $adapter,
        );

        $message = new Message('simple', 'middleware-main');
        $queue->push(
            $message,
        );

        $this->expectException(NotImplementedException::class);
        $adapter->status($message->getId());
    }
}
