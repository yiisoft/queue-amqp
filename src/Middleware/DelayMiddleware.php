<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Middleware;

use Yiisoft\Queue\Message\DelayEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;

final class DelayMiddleware implements PushMiddlewareInterface
{
    public function __construct(private float $delayInSeconds) {}

    /**
     * @param float $seconds
     *
     * @return $this
     */
    public function withDelay(float $seconds): self
    {
        $new = clone $this;
        $new->delayInSeconds = $seconds;

        return $new;
    }

    public function getDelay(): float
    {
        return $this->delayInSeconds;
    }

    public function processPush(MessageInterface $message, PushHandlerInterface $handler): MessageInterface
    {
        if ($this->delayInSeconds <= 0) {
            return $handler->handlePush($message);
        }

        return $handler->handlePush(new DelayEnvelope($message, $this->delayInSeconds));
    }
}
