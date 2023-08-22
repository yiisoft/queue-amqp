<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Integration;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\Middleware\DelayMiddleware;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FakeAdapter;
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
        $this->exchangeName = 'yii-queue.dlx';

        $fileHelper = new FileHelper();
        $adapter = new Adapter(
            new QueueProvider(
                $this->createConnection(),
                new QueueSettings(),
            ),
            new MessageSerializer(),
            new SignalLoop(),
        );
        $queue = $this->makeQueue($adapter);

        $time = time();
        $queue->push(
            new Message('simple', 'test-delay-middleware-main'),
            new DelayMiddleware(3),
        );

        sleep(2);
        self::assertNull($fileHelper->get('test-delay-middleware-main'));
        sleep(2);
        $result = $fileHelper->get('test-delay-middleware-main');
        self::assertNotNull($result);
        $result = (int) $result;
        self::assertGreaterThanOrEqual($time + 3, $result);
        self::assertLessThanOrEqual($time + 5, $result);
    }

    public function testMainFlowWithFakeAdapter(): void
    {
        $adapterClass = Adapter::class;
        $fakeAdapterClass = FakeAdapter::class;

        $adapter = new FakeAdapter(
            new QueueProvider(
                $this->createConnection(),
                new QueueSettings(),
            ),
            new MessageSerializer(),
            new SignalLoop(),
        );
        $queue = $this->makeQueue($adapter);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This middleware works only with the $adapterClass. $fakeAdapterClass given.");
        $queue->push(
            new Message('simple', 'test-delay-middleware-main'),
            new DelayMiddleware(3),
        );
    }

    private function makeQueue(AdapterInterface $adapter): Queue
    {
        return new Queue(
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
    }
}
