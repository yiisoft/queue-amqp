<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Settings;

use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Quality of Service settings for AMQP consumers.
 *
 * @see AMQPChannel::basic_qos()
 */
final class QosSettings
{
    public function __construct(
        private readonly int $prefetchSize = 0,
        private readonly int $prefetchCount = 0,
        private readonly bool $global = false,
    ) {
        if ($prefetchSize < 0) {
            throw new InvalidArgumentException('Prefetch size must be a non-negative integer.');
        }

        if ($prefetchCount < 0) {
            throw new InvalidArgumentException('Prefetch count must be a non-negative integer.');
        }
    }

    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }

    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    public function isGlobal(): bool
    {
        return $this->global;
    }
}
