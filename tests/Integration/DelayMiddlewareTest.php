<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Integration;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\Middleware\DelayMiddleware;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\AMQP\Tests\Support\ExtendedSimpleMessage;
use Yiisoft\Queue\AMQP\Tests\Support\ExtendedSimpleMessageHandler;
use Yiisoft\Queue\AMQP\Tests\Support\FakeAdapter;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactory;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Tests\Shared\ExceptionMessage;
use Yiisoft\Queue\Tests\Shared\ExceptionMessageHandler;
use Yiisoft\Queue\Tests\Shared\NullMessage;
use Yiisoft\Queue\Tests\Shared\NullMessageHandler;
use Yiisoft\Queue\Tests\Shared\StackMessage;
use Yiisoft\Queue\Tests\Shared\StackMessageHandler;
use Yiisoft\Queue\Worker\Worker;
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
            new JsonMessageSerializer(),
            new SignalLoop(),
        );
        $queue = $this->makeQueue($adapter);

        $time = time();
        $file = 'test-delay-middleware-main';
        $queue->push(
            new ExtendedSimpleMessage(['file_name' => $file, 'payload' => ['time' => $time]]),
            fn (Injector $injector) => $injector->make(DelayMiddleware::class, ['delayInSeconds' => 3]),
        );

        sleep(2);
        self::assertNull($fileHelper->get($file));
        sleep(2);
        $result = $fileHelper->get($file);
        self::assertNotNull($result);
        $result = (int) $result;
        self::assertGreaterThanOrEqual($time + 3, $result);
        self::assertLessThanOrEqual($time + 5, $result);
    }

    public function testMainFlowWithFakeAdapter(): void
    {
        $queue = $this->makeQueue(new FakeAdapter());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            sprintf(
                'This middleware works only with the %s. %s given.',
                Adapter::class,
                FakeAdapter::class,
            )
        );
        $queue->push(
            new Message('test-delay-middleware-main'),
            fn (Injector $injector) => $injector->make(DelayMiddleware::class, ['delayInSeconds' => 3]),
        );
    }

    private function makeQueue(AdapterInterface $adapter): Queue
    {
        $definitions = [
            AdapterInterface::class => $adapter,
        ];
        $middlewareDispatcher = new MiddlewareDispatcher(
            new MiddlewareFactory(
                $container = new SimpleContainer([
                    ...$definitions,
                    ExtendedSimpleMessageHandler::class => new ExtendedSimpleMessageHandler(new FileHelper()),
                    Injector::class => new Injector(new SimpleContainer($definitions)),
                ]),
                new CallableFactory($this->createMock(ContainerInterface::class)),
            ),
        );
        $listeners = new ListenerCollection();
        $listeners = $listeners
            ->add(fn (NullMessage $message) => $container->get(NullMessageHandler::class)->handle($message))
            ->add(fn (StackMessage $message) => $container->get(StackMessageHandler::class)->handle($message))
            ->add(fn (ExtendedSimpleMessage $message) => $container->get(ExtendedSimpleMessageHandler::class)->handle($message))
            ->add(fn (ExceptionMessage $message) => $container->get(ExceptionMessageHandler::class)->handle($message));

        return new Queue(
            new Worker(
                $logger = new NullLogger(),
                new Dispatcher(new Provider($listeners)),
                $container,
                $middlewareDispatcher,
                $middlewareDispatcher,
            ),
            $this->createMock(LoopInterface::class),
            $logger,
            $middlewareDispatcher,
            $adapter,
        );
    }
}
