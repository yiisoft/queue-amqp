<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Yiisoft\Yii\Queue\AMQP\Exception\ExchangeDeclaredException;
use Yiisoft\Yii\Queue\AMQP\Exception\NoKeyInPayloadException;

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
