<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Yiisoft\Queue\AMQP\Middleware\DelayMiddleware;

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
}
