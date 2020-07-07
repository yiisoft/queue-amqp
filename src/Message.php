<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver\Interop;

use RuntimeException;
use Yiisoft\Yii\Queue\Job\JobInterface;
use Yiisoft\Yii\Queue\MessageInterface;

class Message implements MessageInterface
{
    private JobInterface $job;

    public function __construct(JobInterface $job)
    {
        $this->job = $job;
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        throw new RuntimeException('Driver doesn\'t support message ids');
    }

    /**
     * @inheritDoc
     */
    public function getJob(): JobInterface
    {
        // TODO: Implement getJob() method.
    }
}
