<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageTrait;

/**
 * Accepts any values from the queue and writes to the file
 */
final class ExtendedSimpleMessage implements MessageInterface
{
    use MessageTrait;

    public function __construct(
        array $data,
    )
    {
        $this->data = $data;
    }
}
