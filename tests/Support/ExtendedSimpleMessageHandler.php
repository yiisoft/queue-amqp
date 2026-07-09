<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Support;

use Yiisoft\Queue\Message\MessageInterface;

/**
 * Accepts any values from the queue and writes to the file
 */
final class ExtendedSimpleMessageHandler
{
    public function __construct(private readonly FileHelper $fileHelper)
    {
    }

    public function handle(MessageInterface $message): void
    {
        $data = $message->getPayload();
        if (null !== $data) {
            $this->fileHelper->put($data['file_name'], $data['payload']['time']);
        }
    }
}
