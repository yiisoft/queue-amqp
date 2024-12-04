<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use PHPUnit\Util\Exception as PHPUnitException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\AMQP\Tests\Support\ExtendedSimpleMessage;
use Yiisoft\Queue\AMQP\Tests\Support\ExtendedSimpleMessageHandler;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\AMQP\Tests\Support\MainTestCase;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\MessageInterface;
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
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;

/**
 * Test case for unit tests
 */
abstract class UnitTestCase extends MainTestCase
{
    protected Dispatcher $eventDispatcher;
    protected Queue|null $queue = null;
    protected ?WorkerInterface $worker = null;
    protected ?ContainerInterface $container = null;
    protected ?AdapterInterface $adapter = null;
    protected ?LoopInterface $loop = null;
    public ?QueueSettings $queueSettings = null;
    public ?QueueProvider $queueProvider = null;

    protected function setUp(): void
    {
        (new FileHelper())->clear();

        $this->deleteQueue();
        $this->deleteExchange();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        (new FileHelper())->clear();

        $this->deleteQueue();
        $this->deleteExchange();

        parent::tearDown();
    }

    protected function getQueue(): Queue
    {
        return $this->queue ??= new Queue(
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger(),
            $this->getPushMiddlewareDispatcher()
        );
    }

    protected function getWorker(): WorkerInterface
    {
        return $this->worker ??= new Worker(
            new NullLogger(),
            $this->createEventDispatcher(),
            $this->createContainer(),
            $this->getConsumeMiddlewareDispatcher(),
            $this->getFailureMiddlewareDispatcher(),
        );
    }

    protected function getMessageHandlers(): array
    {
        return [
            'ext-simple' => [new ExtendedSimpleMessageHandler(new FileHelper()), 'handle'],
            'exception-listen' => static function (MessageInterface $message) {
                $data = $message->getData();
                if (null !== $data) {
                    throw new PHPUnitException((string) $data['payload']['time']);
                }
            },
        ];
    }

    protected function createContainer(): ContainerInterface
    {
        return $this->container ??= new SimpleContainer($this->getContainerDefinitions());
    }

    protected function getContainerDefinitions(): array
    {
        return [
            ExtendedSimpleMessageHandler::class => new ExtendedSimpleMessageHandler(new FileHelper()),
            ExceptionMessageHandler::class => new ExceptionMessageHandler(),
            StackMessageHandler::class => new StackMessageHandler(),
            NullMessageHandler::class => new NullMessageHandler(),
        ];
    }

    protected function getConsumeMiddlewareDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory(
                $this->createContainer(),
                new CallableFactory($this->createContainer()),
            ),
        );
    }

    protected function getFailureMiddlewareDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory(
                $this->createContainer(),
                new CallableFactory($this->createContainer()),
            ),
        );
    }

    protected function getAdapter(): AdapterInterface
    {
        return $this->adapter ??= new Adapter(
            $this->getQueueProvider(),
            new JsonMessageSerializer(),
            $this->getLoop(),
        );
    }

    protected function getLoop(): LoopInterface
    {
        return $this->loop ??= new SignalLoop();
    }

    protected function getPushMiddlewareDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory(
                $this->createContainer(),
                new CallableFactory($this->createContainer()),
            ),
        );
    }

    protected function getQueueSettings(): QueueSettings
    {
        return $this->queueSettings ??= new QueueSettings();
    }

    protected function getQueueProvider(): QueueProvider
    {
        return $this->queueProvider ??= new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
    }

    protected function createEventDispatcher(): EventDispatcherInterface
    {
        $container = $this->createContainer();
        $listeners = new ListenerCollection();
        $listeners = $listeners
            ->add(fn (NullMessage $message) => $container->get(NullMessageHandler::class)->handle($message))
            ->add(fn (StackMessage $message) => $container->get(StackMessageHandler::class)->handle($message))
            ->add(fn (ExtendedSimpleMessage $message) => $container->get(ExtendedSimpleMessageHandler::class)->handle($message))
            ->add(fn (ExceptionMessage $message) => $container->get(ExceptionMessageHandler::class)->handle($message));

        return $this->eventDispatcher ??= new Dispatcher(new Provider($listeners));
    }
}
