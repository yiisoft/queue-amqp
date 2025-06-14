<?php

declare(strict_types=1);

namespace Benchmark;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpBench\Attributes\OutputMode;
use PhpBench\Attributes\OutputTimeUnit;
use PhpBench\Attributes\Skip;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;

final class QueueBench
{
    private const CONSUME_REVISIONS = 100_000;

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

    #[\PhpBench\Attributes\BeforeMethods('cleanupQueue')]
    #[OutputMode('throughput')]
    #[OutputTimeUnit('seconds', 3)]
    #[Skip]
    public function benchPush(): void
    {
        $this->adapter->push(new Message('test', ['payload' => 'test']));
    }

    public function cleanupQueue(): void
    {
        $this->adapter->runExisting(static fn (): bool => true);
    }

    #[\PhpBench\Attributes\Iterations(5)]
    #[\PhpBench\Attributes\Revs(self::CONSUME_REVISIONS)]
    #[\PhpBench\Attributes\BeforeMethods('pushMessagesForConsume')]
    #[OutputMode('throughput')]
    #[OutputTimeUnit('seconds', 3)]
    public function benchConsume(): void
    {
        $this->adapter->runExisting(static fn (): bool => false);
    }

    public function pushMessagesForConsume(): void
    {
        for ($i = 0; $i < self::CONSUME_REVISIONS; $i++) {
            $this->adapter->push(new Message('test', ['payload' => 'test']));
        }
    }
}
