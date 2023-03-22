<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Integration;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Process\Process;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Exception;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
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

abstract class TestCase extends PhpUnitTestCase
{
    public AMQPStreamConnection $connection;
    protected Queue|null $queue = null;
    protected ?WorkerInterface $worker = null;
    protected ?ContainerInterface $container = null;
    protected int $executionTimes;

    /** @var Process[] */
    private array $processes = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createConnection();
        $this->executionTimes = 0;

        (new FileHelper())->clear();
    }

    protected function tearDown(): void
    {
        foreach ($this->processes as $process) {
            $process->stop();
        }
        $this->processes = [];

        (new FileHelper())->clear();

        parent::tearDown();
    }

    protected function queueListen(?string $queue = null): void
    {
        // TODO Fail test on subprocess error exit code
        $command = [PHP_BINARY, dirname(__DIR__) . '/yii', 'queue/listen'];
        if ($queue !== null) {
            $command[] = "--channel=$queue";
        }
        $process = new Process($command);
        $this->processes[] = $process;
        $process->start();
    }

    /**
     * @throws Exception
     *
     * @return AMQPStreamConnection
     */
    private function createConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
    }

    protected function getQueue(): Queue
    {
        if ($this->queue === null) {
            $this->queue = $this->createQueue();
        }

        return $this->queue;
    }

    protected function createQueue(): Queue
    {
        return new Queue(
            $this->getWorker(),
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            new PushMiddlewareDispatcher(
                new MiddlewareFactoryPush(
                    $this->createMock(ContainerInterface::class),
                    new CallableFactory($this->createMock(ContainerInterface::class)),
                ),
            )
        );
    }

    protected function getWorker(): WorkerInterface
    {
        if ($this->worker === null) {
            $this->worker = $this->createWorker();
        }

        return $this->worker;
    }

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

    protected function getMessageHandlers(): array
    {
        return [
            'simple' => fn () => $this->executionTimes++,
        ];
    }

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
}
