<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Support;

use Yiisoft\Yii\Queue\Message\MessageInterface;

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
