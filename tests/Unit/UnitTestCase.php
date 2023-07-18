<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use PHPUnit\Util\Exception as PHPUnitException;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\ExtendedSimpleMessageHandler;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Worker\Worker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/**
 * Test case for unit tests
 */
abstract class UnitTestCase extends PhpUnitTestCase
{
    protected Queue|null $queue = null;
    protected ?WorkerInterface $worker = null;
    protected ?ContainerInterface $container = null;
    protected ?AdapterInterface $adapter = null;
    protected ?LoopInterface $loop = null;
    public ?QueueSettings $queueSettings = null;
    public ?QueueProvider $queueProvider = null;

    protected function setUp(): void
    {
        parent::setUp();

        (new FileHelper())->clear();
    }

    protected function tearDown(): void
    {
        (new FileHelper())->clear();

        parent::tearDown();
    }

    /**
     * @throws Exception
     *
     * @return AMQPStreamConnection
     */
    protected function createConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD')
        );
    }

    /**
     * @return Queue
     */
    protected function getQueue(): Queue
    {
        if ($this->queue === null) {
            $this->queue = $this->createQueue();
        }

        return $this->queue;
    }

    /**
     * @return Queue
     */
    protected function createQueue(): Queue
    {
        return new Queue(
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger(),
            $this->getPushMiddlewareDispatcher()
        );
    }

    /**
     * @return WorkerInterface
     */
    protected function getWorker(): WorkerInterface
    {
        if ($this->worker === null) {
            $this->worker = $this->createWorker();
        }

        return $this->worker;
    }

    /**
     * @return WorkerInterface
     */
    protected function createWorker(): WorkerInterface
    {
        return new Worker(
            $this->getMessageHandlers(),
            new NullLogger(),
            new Injector($this->getContainer()),
            $this->getContainer(),
            $this->getConsumeMiddlewareDispatcher(),
            $this->getFailureMiddlewareDispatcher(),
        );
    }

    /**
     * @return array
     */
    protected function getMessageHandlers(): array
    {
        return [
            'ext-simple' => [new ExtendedSimpleMessageHandler(new FileHelper()), 'handle'],
            'exception-listen' => static function (MessageInterface $message) {
                $data = $message->getData();
                if (null !== $data) {
                    throw new PHPUnitException((string)$data['payload']['time']);
                }
            },
        ];
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            $this->container = $this->createContainer();
        }

        return $this->container;
    }

    protected function createContainer(): ContainerInterface
    {
        return new SimpleContainer($this->getContainerDefinitions());
    }

    /**
     * @return array
     */
    protected function getContainerDefinitions(): array
    {
        return [];
    }

    protected function getConsumeMiddlewareDispatcher(): ConsumeMiddlewareDispatcher
    {
        return new ConsumeMiddlewareDispatcher(
            new MiddlewareFactoryConsume(
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
            ),
        );
    }

    protected function getFailureMiddlewareDispatcher(): FailureMiddlewareDispatcher
    {
        return new FailureMiddlewareDispatcher(
            new MiddlewareFactoryFailure(
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
            ),
            [],
        );
    }

    /**
     * @throws Exception
     *
     * @return AdapterInterface
     */
    protected function getAdapter(): AdapterInterface
    {
        if ($this->adapter === null) {
            $this->adapter = $this->createAdapter();
        }

        return $this->adapter;
    }

    /**
     * @throws Exception
     *
     * @return AdapterInterface
     */
    protected function createAdapter(): AdapterInterface
    {
        return new Adapter(
            $this->getQueueProvider(),
            new MessageSerializer(),
            $this->getLoop(),
        );
    }

    /**
     * @return LoopInterface
     */
    protected function getLoop(): LoopInterface
    {
        if ($this->loop === null) {
            $this->loop = $this->createLoop();
        }

        return $this->loop;
    }

    /**
     * @return LoopInterface
     */
    protected function createLoop(): LoopInterface
    {
        return new SignalLoop();
    }

    protected function getPushMiddlewareDispatcher(): PushMiddlewareDispatcher
    {
        return new PushMiddlewareDispatcher(
            new MiddlewareFactoryPush(
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
            ),
        );
    }

    /**
     * @return QueueSettings
     */
    protected function getQueueSettings(): QueueSettings
    {
        if (null === $this->queueSettings) {
            $this->queueSettings = $this->createQueueSettings();
        }

        return $this->queueSettings;
    }

    /**
     * @return QueueSettings
     */
    protected function createQueueSettings(): QueueSettings
    {
        return new QueueSettings();
    }

    /**
     * @throws Exception
     *
     * @return QueueProvider
     */
    protected function getQueueProvider(): QueueProvider
    {
        if (null === $this->queueProvider) {
            $this->queueProvider = $this->createQueueProvider();
        }

        return $this->queueProvider;
    }

    /**
     * @throws Exception
     *
     * @return QueueProvider
     */
    protected function createQueueProvider(): QueueProvider
    {
        return new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
    }
}
