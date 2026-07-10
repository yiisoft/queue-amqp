<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Support;

use Yiisoft\Queue\Message\MessageInterface;

use function is_string;

final class SimpleMessageHandler
{
    public function __construct(private readonly FileHelper $fileHelper) {}

    public function __invoke(MessageInterface $message): void
    {
        $fileName = $message->getPayload();
        if (is_string($fileName)) {
            $this->fileHelper->put($fileName, time());
        }
    }
}
