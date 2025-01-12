<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\MessageSerializer;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;

final class QueueSettingsTest extends UnitTestCase
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
                    new QueueSettings(
                        queueName: 'yii-queue-test-queue-common-settings',
                        passive: true,
                        durable: true,
                        exclusive: true,
                        nowait: true,
                        arguments: new AMQPTable([
                            'x-dead-letter-exchange' => 'yii-queue-test-queue-common-settings-dead-letter-exc',
                            'x-message-ttl' => 15000,
                            'x-expires' => 16000,
                        ])
                    )
                )
                ->withExchangeSettings(
                    new ExchangeSettings('yii-queue-test-queue-common-settings')
                ),
            new JsonMessageSerializer(),
            $this->getLoop(),
        );

        $queueSettings = $adapter->getQueueProvider()->getQueueSettings();

        self::assertTrue($queueSettings->isDurable());
        self::assertTrue($queueSettings->isPassive());
        self::assertTrue($queueSettings->isExclusive());
        self::assertTrue($queueSettings->isAutoDeletable());
        self::assertTrue($queueSettings->hasNowait());
        self::assertNull($queueSettings->getTicket());

        self::assertFalse($queueSettings->withDurable(false)->isDurable());
        self::assertFalse($queueSettings->withPassive(false)->isPassive());
        self::assertFalse($queueSettings->withExclusive(false)->isExclusive());
        self::assertFalse($queueSettings->withAutoDeletable(false)->isAutoDeletable());
        self::assertFalse($queueSettings->withNowait(false)->hasNowait());
        self::assertEquals(0, $queueSettings->withTicket(0)->getTicket());

        self::assertInstanceOf(AMQPTable::class, $queueSettings->getArguments());
        self::assertArrayHasKey('x-dead-letter-exchange', $queueSettings->getArguments());
        self::assertArrayHasKey('x-message-ttl', $queueSettings->getArguments());
        self::assertArrayHasKey('x-expires', $queueSettings->getArguments());
    }

    public function testArgumentsXExpires(): void
    {
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueSettings(
                    new QueueSettings(
                        queueName: 'yii-queue-test-queue-settings-arg',
                        arguments: new AMQPTable([
                            'x-expires' => 1600,
                        ])
                    )
                )
                ->withExchangeSettings(
                    new ExchangeSettings('yii-queue-test-queue-settings-arg')
                ),
            new JsonMessageSerializer(),
            $this->getLoop(),
        );

        $this->getQueue()
            ->withAdapter($adapter)
            ->push(
                new Message('ext-simple', ['payload' => time()])
            );

        sleep(2);
        $this->expectException(AMQPProtocolChannelException::class);
        $this->createConnection()
            ->channel()
            ->basic_get('yii-queue-test-queue-settings-arg');
    }

    public function testImmutable(): void
    {
        $queueSettings = new QueueSettings();

        self::assertNotSame($queueSettings, $queueSettings->withPassive(true));
        self::assertNotSame($queueSettings, $queueSettings->withNowait(true));
        self::assertNotSame($queueSettings, $queueSettings->withExclusive(true));
        self::assertNotSame($queueSettings, $queueSettings->withDurable(true));
        self::assertNotSame($queueSettings, $queueSettings->withTicket(0));
        self::assertNotSame($queueSettings, $queueSettings->withName('test'));
        self::assertNotSame($queueSettings, $queueSettings->withAutoDeletable(false));
        self::assertNotSame($queueSettings, $queueSettings->withArguments([]));
    }
}
