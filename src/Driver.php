<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\Interop;

use AMQPConnection;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Job\JobInterface;
use Yiisoft\Yii\Queue\MessageInterface;

class Driver implements DriverInterface
{
    /**
     * @inheritDoc
     */
    public function nextMessage(): ?MessageInterface
    {
        // TODO: Implement nextMessage() method.
    }

    /**
     * @inheritDoc
     */
    public function status(string $id): JobStatus
    {
        // TODO: Implement status() method.
    }

    /**
     * @inheritDoc
     */
    public function push(JobInterface $job): MessageInterface
    {
        // TODO: Implement push() method.
    }

    /**
     * @inheritDoc
     */
    public function subscribe(callable $handler): void
    {
        // TODO: Implement subscribe() method.
    }

    /**
     * @inheritDoc
     */
    public function canPush(JobInterface $job): bool
    {
        // TODO: Implement canPush() method.
    }
}
