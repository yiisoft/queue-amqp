<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Amqp\Settings\QosSettings;

final class QosSettingsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $settings = new QosSettings();

        self::assertSame(0, $settings->getPrefetchSize());
        self::assertSame(0, $settings->getPrefetchCount());
        self::assertFalse($settings->isGlobal());
    }

    public function testValues(): void
    {
        $settings = new QosSettings(
            prefetchSize: 1024,
            prefetchCount: 10,
            global: true,
        );

        self::assertSame(1024, $settings->getPrefetchSize());
        self::assertSame(10, $settings->getPrefetchCount());
        self::assertTrue($settings->isGlobal());
    }

    public function testNegativePrefetchSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prefetch size must be a non-negative integer.');

        new QosSettings(prefetchSize: -1);
    }

    public function testNegativePrefetchCount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prefetch count must be a non-negative integer.');

        new QosSettings(prefetchCount: -1);
    }
}
