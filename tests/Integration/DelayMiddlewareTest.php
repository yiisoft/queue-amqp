<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Integration;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Amqp\Adapter;
use Yiisoft\Queue\Amqp\Middleware\DelayMiddleware;
use Yiisoft\Queue\Amqp\QueueProvider;
use Yiisoft\Queue\Amqp\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Amqp\Settings\Exchange as ExchangeSettings;
use Yiisoft\Queue\Amqp\Tests\Support\FakeAdapter;
use Yiisoft\Queue\Amqp\Tests\Support\FileHelper;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Amqp\Tests\Support\TestMessage as Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use LogicException;

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
                new ExchangeSettings('yii-queue'),
            ),
            new MessageSerializer(new JsonMessageEncoder()),
            new SignalLoop(),
        );
        $queue = $this->makeQueue($adapter, new DelayMiddleware(3));

        $time = time();
        $queue->push(
            Message::fromPayload('simple', 'test-delay-middleware-main'),
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
        $adapter = new FakeAdapter(
            new QueueProvider(
                $this->createConnection(),
                new QueueSettings(),
                new ExchangeSettings('yii-queue'),
            ),
            new MessageSerializer(new JsonMessageEncoder()),
            new SignalLoop(),
        );
        $queue = $this->makeQueue($adapter, new DelayMiddleware(3));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Method not implemented');
        $queue->push(
            Message::fromPayload('simple', 'test-delay-middleware-main'),
        );
    }

    private function makeQueue(AdapterInterface $adapter, mixed ...$middlewareDefinitions): Queue
    {
        return new Queue(
            $this->createMock(WorkerInterface::class),
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            new PushMiddlewareConfig(
                new PushMiddlewareFactory(
                    new SimpleContainer(),
                    new CallableFactory($this->createMock(ContainerInterface::class)),
                ),
            ),
            $adapter,
            QueueProviderInterface::DEFAULT_QUEUE,
            ...$middlewareDefinitions,
        );
    }
}
