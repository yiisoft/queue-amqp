<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Integration;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\Middleware\DelayMiddleware;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class DelayMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->queueListen();
    }

    public function testMainFlow(): void
    {
        $fileHelper = new FileHelper();
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
            new Adapter(
                new QueueProvider(
                    new AMQPStreamConnection(
                        getenv('RABBITMQ_HOST'),
                        getenv('RABBITMQ_PORT'),
                        getenv('RABBITMQ_USER'),
                        getenv('RABBITMQ_PASSWORD'),
                    ),
                    new QueueSettings(),
                ),
                new MessageSerializer(),
                new SignalLoop(),
            ),
        );

        $time = time();
        $queue->push(
            new Message('simple', 'test-delay-middleware-main'),
            new DelayMiddleware(3),
        );

        //sleep(60);
        self::assertNull($fileHelper->get('test-delay-middleware-main'));
        //sleep(2);
        $result = $fileHelper->get('test-delay-middleware-main');
        self::assertNotNull($result);
        $result = (int) $result;
        self::assertTrue($result >= $time + 3);
        self::assertTrue($result <= $time + 4);
    }
}
