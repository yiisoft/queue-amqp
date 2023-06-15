<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Yiisoft\Yii\Queue\AMQP\Middleware\DelayMiddleware;

final class DelayMiddlewareTest extends UnitTestCase
{
    public function testWithDelay(): void
    {
        $delayMiddleware = new DelayMiddleware(5);

        self::assertEquals(5, $delayMiddleware->getDelay());

        $delayMiddlewareWithDelay = $delayMiddleware->withDelay(10);
        self::assertEquals(10, $delayMiddlewareWithDelay->getDelay());
    }
}
