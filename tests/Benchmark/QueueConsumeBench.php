<?php

declare(strict_types=1);

namespace Benchmark;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;

#[BeforeClassMethods('cleanupQueue')]
final class QueueConsumeBench
{
    private const MESSAGE_COUNT = 10_000;

    private Adapter $adapter;

    public function __construct()
    {
        $this->adapter = self::getAdapter();
    }

    /**
     * How fast we can consume 10_000 messages
     */
    #[Iterations(5)]
    #[Revs(1)]
    #[BeforeMethods('pushMessagesForConsume')]
    public function benchConsume(): void
    {
        $this->adapter->runExisting(static fn(): bool => true);
    }

    public function pushMessagesForConsume(): void
    {
        for ($i = 0; $i < self::MESSAGE_COUNT; $i++) {
            $this->adapter->push(new Message('test', ['payload' => 'test']));
        }
    }

    public static function cleanupQueue(): void
    {
        self::getAdapter()->runExisting(static fn(): bool => true);
    }

    private static function getAdapter(): Adapter
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
            new QueueSettings(),
        );
        return new Adapter($queueProvider, $serializer, $loop);
    }
}
