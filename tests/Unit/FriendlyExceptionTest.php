<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Yiisoft\Queue\AMQP\Exception\ExchangeDeclaredException;
use Yiisoft\Queue\AMQP\Exception\NoKeyInPayloadException;

final class FriendlyExceptionTest extends UnitTestCase
{
    public function testNoKeyInPayloadException(): void
    {
        $exception = new NoKeyInPayloadException(
            'test',
            ['item1', 'item2']
        );

        self::assertSame('No key "test" in payload', $exception->getName());
        $this->assertMatchesRegularExpression('/test/', $exception->getSolution());
    }

    public function testExchangeDeclaredException(): void
    {
        $exception = new ExchangeDeclaredException();

        self::assertSame('Exchange is declared', $exception->getName());
    }
}
