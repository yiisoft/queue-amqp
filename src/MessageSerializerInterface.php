<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP;

use Yiisoft\Queue\Message\MessageInterface;

interface MessageSerializerInterface
{
    public function serialize(MessageInterface $message): string;

    public function unserialize(string $value): MessageInterface;
}
