<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use Yiisoft\Yii\Queue\Message\MessageInterface;

interface MessageSerializerInterface
{
    public function serialize(MessageInterface $message): string;

    public function unserialize(string $value): MessageInterface;
}
