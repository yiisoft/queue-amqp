<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Support;

use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\AMQP\MessageSerializerInterface;
use Yiisoft\Yii\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;

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
