<?php

declare(strict_types=1);

namespace Benchmark;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;

final class QueueBench
{
    private const CONSUME_MESSAGE_COUNT = 10_000;

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
    #[Revs(self::CONSUME_MESSAGE_COUNT)]
    #[BeforeMethods('cleanupQueue')]
    #[AfterMethods('cleanupQueue')]
    public function benchPush(): void
    {
        $this->adapter->push(new Message('test', ['payload' => 'test']));
    }

    /**
     * How fast we can consume 100_000 messages
     */
    #[Iterations(5)]
    #[Revs(1)]
    #[BeforeMethods('cleanupQueue')]
    #[BeforeMethods('pushMessagesForConsume')]
    public function benchConsume(): void
    {
        $this->adapter->runExisting(static fn (): bool => true);
    }

    public function pushMessagesForConsume(): void
    {
        for ($i = 0; $i < self::CONSUME_MESSAGE_COUNT; $i++) {
            $this->adapter->push(new Message('test', ['payload' => 'test']));
        }
    }

    public function cleanupQueue(): void
    {
        $this->adapter->runExisting(static fn (): bool => true);
    }
}
