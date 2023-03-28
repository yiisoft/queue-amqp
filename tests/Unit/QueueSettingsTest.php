<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Integration\TestCase;
use Yiisoft\Yii\Queue\Message\Message;

final class QueueSettingsTest extends TestCase
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
            new MessageSerializer(),
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
            new MessageSerializer(),
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
}
