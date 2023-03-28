<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Integration\TestCase;

final class ExchangeSettingsTest extends TestCase
{
    public function testCommonSettings(): void
    {
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueSettings(
                    new QueueSettings('yii-queue-test-common-settings')
                )
                ->withExchangeSettings(
                    new ExchangeSettings(
                        exchangeName: 'yii-queue-test-common-settings',
                        passive: true,
                        durable: true,
                        autoDelete: false,
                        internal: true,
                        nowait: true,
                        arguments: new AMQPTable([
                            'alternate-exchange' => 'yii-queue-test-common-settings-alt',
                        ])
                    )
                ),
            new MessageSerializer(),
            $this->getLoop(),
        );
        $exchangeSettings = $adapter->getQueueProvider()->getExchangeSettings();

        self::assertTrue($exchangeSettings->isDurable());
        self::assertTrue($exchangeSettings->isInternal());
        self::assertTrue($exchangeSettings->isPassive());
        self::assertTrue($exchangeSettings->hasNowait());
        self::assertFalse($exchangeSettings->isAutoDelete());
        self::assertNull($exchangeSettings->getTicket());
        self::assertEquals(AMQPExchangeType::DIRECT, $exchangeSettings->getType());

        self::assertFalse($exchangeSettings->withDurable(false)->isDurable());
        self::assertFalse($exchangeSettings->withInternal(false)->isInternal());
        self::assertFalse($exchangeSettings->withPassive(false)->isPassive());
        self::assertFalse($exchangeSettings->withNowait(false)->hasNowait());
        self::assertTrue($exchangeSettings->withAutoDelete(true)->isAutoDelete());
        self::assertEquals(0, $exchangeSettings->withTicket(0)->getTicket());

        self::assertInstanceOf(AMQPTable::class, $exchangeSettings->getArguments());
        self::assertArrayHasKey('alternate-exchange', $exchangeSettings->getArguments());
        self::assertEmpty($exchangeSettings->withArguments([])->getArguments());
    }
}
