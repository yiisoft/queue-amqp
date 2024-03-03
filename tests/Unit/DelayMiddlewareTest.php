<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\Middleware\DelayMiddleware;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

final class DelayMiddlewareTest extends UnitTestCase
{
    public function testWithDelay(): void
    {
        $adapter = new Adapter(
            $this->createMock(QueueProviderInterface::class),
            $this->createMock(MessageSerializerInterface::class),
            $this->createMock(LoopInterface::class),
        );
        $delayMiddleware = new DelayMiddleware($adapter, 5);

        self::assertEquals(5, $delayMiddleware->getDelay());

        $delayMiddlewareWithDelay = $delayMiddleware->withDelay(10);
        self::assertNotSame($delayMiddleware, $delayMiddlewareWithDelay);

        self::assertEquals(10, $delayMiddlewareWithDelay->getDelay());
    }
}
