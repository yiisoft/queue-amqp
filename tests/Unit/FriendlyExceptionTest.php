<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Unit;

use Yiisoft\Queue\Amqp\Exception\ExchangeDeclaredException;

final class FriendlyExceptionTest extends UnitTestCase
{
    public function testExchangeDeclaredException(): void
    {
        $exception = new ExchangeDeclaredException();

        self::assertSame('Exchange is declared', $exception->getName());
    }
}
