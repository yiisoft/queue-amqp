<?php

declare(strict_types=1);

namespace Benchmark;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\OutputMode;
use PhpBench\Attributes\OutputTimeUnit;
use PhpBench\Attributes\Revs;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;

final class QueuePushBench
{
    private const MESSAGE_COUNT = 10_000;

    private Adapter $adapter;

    public function __construct()
    {
        $loop = new SimpleLoop();
        $serializer = new JsonMessageSerializer();
        $queueProvider = new QueueProvider(
            new AMQPStreamConnection(
                getenv('RABBITMQ_HOST'),
                getenv('RABBITMQ_PORT'),
                getenv('RABBITMQ_USER'),
                getenv('RABBITMQ_PASSWORD'),
            ),
            new QueueSettings()
        );
        $adapter = new Adapter($queueProvider, $serializer, $loop);

        $this->adapter = $adapter;
    }

    /**
     * How fast we can push 1 message
     */
    #[Iterations(5)]
    #[Revs(self::MESSAGE_COUNT)]
    #[BeforeMethods('cleanupQueue')]
    #[AfterMethods('cleanupQueue')]
    #[OutputMode('throughput')]
    #[OutputTimeUnit('seconds')]
    public function benchPush(): void
    {
        $this->adapter->push(new Message('test', ['payload' => 'test']));
    }

    /**
     * How fast we can push 100 messages
     */
    #[Iterations(5)]
    #[Revs(100)]
    #[BeforeMethods('cleanupQueue')]
    #[AfterMethods('cleanupQueue')]
    #[OutputMode('throughput')]
    #[OutputTimeUnit('seconds')]
    public function benchPushBatch(): void
    {
        $message = new Message('test', ['payload' => 'test']);
        for ($i = 0; $i < 100; $i++) {
            $this->adapter->push($message);
        }
    }

    public function cleanupQueue(): void
    {
        $this->adapter->runExisting(static fn (): bool => true);
    }
}
