<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Yiisoft\Queue\AMQP\Middleware\DelayMiddleware;
use Yiisoft\Queue\Message\DelayEnvelope;
use Yiisoft\Queue\AMQP\Tests\Support\TestMessage as Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;

final class DelayMiddlewareTest extends UnitTestCase
{
    public function testWithDelay(): void
    {
        $delayMiddleware = new DelayMiddleware(5);

        self::assertEquals(5, $delayMiddleware->getDelay());

        $delayMiddlewareWithDelay = $delayMiddleware->withDelay(10);
        self::assertEquals(10, $delayMiddlewareWithDelay->getDelay());
    }

    public function testImmutable(): void
    {
        $delayMiddleware = new DelayMiddleware(0);

        self::assertNotSame($delayMiddleware, $delayMiddleware->withDelay(1));
    }

    public function testProcessPushAddsDelayEnvelope(): void
    {
        $message = Message::fromData('simple', null);
        $handler = new class () implements PushHandlerInterface {
            public function handlePush(MessageInterface $message): MessageInterface
            {
                return $message;
            }
        };

        $result = (new DelayMiddleware(1.5))->processPush($message, $handler);

        self::assertNotSame($message, $result);
        self::assertSame(1.5, DelayEnvelope::fromMessage($result)->getDelaySeconds());
    }

    public function testProcessPushSkipsNonPositiveDelay(): void
    {
        $message = Message::fromData('simple', null);
        $handler = new class () implements PushHandlerInterface {
            public function handlePush(MessageInterface $message): MessageInterface
            {
                return $message;
            }
        };

        $result = (new DelayMiddleware(0))->processPush($message, $handler);

        self::assertSame($message, $result);
    }
}
