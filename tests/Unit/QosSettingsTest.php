<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\AMQP\Settings\QosSettings;

final class QosSettingsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $settings = new QosSettings();

        self::assertSame(0, $settings->getPrefetchSize());
        self::assertSame(0, $settings->getPrefetchCount());
        self::assertFalse($settings->isGlobal());
    }

    public function testCustomValues(): void
    {
        $settings = new QosSettings(prefetchSize: 1024, prefetchCount: 5, global: true);

        self::assertSame(1024, $settings->getPrefetchSize());
        self::assertSame(5, $settings->getPrefetchCount());
        self::assertTrue($settings->isGlobal());
    }

    public function testNegativePrefetchSizeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prefetch size must be a non-negative integer, -1 given.');

        new QosSettings(prefetchSize: -1);
    }

    public function testNegativePrefetchCountThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prefetch count must be a non-negative integer, -3 given.');

        new QosSettings(prefetchCount: -3);
    }

}
