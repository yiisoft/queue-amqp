<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use PHPUnit\Util\Exception as PHPUnitException;
use Yiisoft\Queue\Message\MessageInterface;

final class ExceptionListener
{
    public function __invoke(MessageInterface $message): void
    {
        $data = $message->getData();
        if (null !== $data) {
            throw new PHPUnitException((string) $data['payload']['time']);
        }
    }
}
