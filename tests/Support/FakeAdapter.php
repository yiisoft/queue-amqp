<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use BackedEnum;
use LogicException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

final class FakeAdapter implements AdapterInterface
{
    public function __construct(
        private readonly QueueProviderInterface $queueProvider,
        private readonly MessageSerializerInterface $serializer,
        private readonly LoopInterface $loop,
    ) {
    }

    public function runExisting(callable $handlerCallback): void
    {
        throw new LogicException('Method not implemented');
    }

    public function status(int|string $id): JobStatus
    {
        throw new LogicException('Method not implemented');
    }

    public function push(MessageInterface $message): MessageInterface
    {
        throw new LogicException('Method not implemented');
    }

    public function subscribe(callable $handlerCallback): void
    {
        throw new LogicException('Method not implemented');
    }

    public function withChannel(BackedEnum|string $channel): AdapterInterface
    {
        throw new LogicException('Method not implemented');
    }

    public function getChannel(): string
    {
        throw new LogicException('Method not implemented');
    }
}
