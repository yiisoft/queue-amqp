<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use Yiisoft\Queue\Message\MessageInterface;

final class SimpleMessageHandler
{
    public function __construct(private FileHelper $fileHelper)
    {
    }

    public function __invoke(MessageInterface $message): void
    {
        $this->fileHelper->put($message->getData(), time());
    }
}
