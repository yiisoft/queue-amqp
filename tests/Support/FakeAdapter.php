<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

final class FakeAdapter implements AdapterInterface
{
    public function __construct(
    ) {
    }

    public function runExisting(callable $handlerCallback): void
    {
    }

    public function status(string|int $id): JobStatus
    {
        // TODO: Implement status() method.
    }

    public function push(MessageInterface $message): MessageInterface
    {
        return $message;
    }

    public function subscribe(callable $handlerCallback): void
    {
    }

    public function withChannel(string $channel): AdapterInterface
    {
        // TODO: Implement withChannel() method.
    }
}
