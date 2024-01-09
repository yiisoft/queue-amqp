<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Integration;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\MessageSerializer;
use Yiisoft\Queue\AMQP\Middleware\DelayMiddleware;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\AMQP\Tests\Support\FakeAdapter;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\AMQP\Tests\Support\SimpleMessageHandler;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;

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
                    new SimpleContainer([
                        'simple' => new SimpleMessageHandler(new FileHelper()),
                    ]),
                    new CallableFactory($this->createMock(ContainerInterface::class)),
                ),
            ),
            $adapter,
        );
    }
}
