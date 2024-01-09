<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\MessageSerializerInterface;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;

final class FakeAdapter implements AdapterInterface
{
    public function __construct(
        private QueueProviderInterface $queueProvider,
        private MessageSerializerInterface $serializer,
        private LoopInterface $loop,
    ) {
    }

    public function runExisting(callable $handlerCallback): void
    {
        // TODO: Implement runExisting() method.
    }

    public function status(string $id): JobStatus
    {
        // TODO: Implement status() method.
    }

    public function push(MessageInterface $message): void
    {
        // TODO: Implement push() method.
    }

    public function subscribe(callable $handlerCallback): void
    {
        // TODO: Implement subscribe() method.
    }

    public function withChannel(string $channel): AdapterInterface
    {
        // TODO: Implement withChannel() method.
    }
}
