<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Unit;

use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Wire\AMQPTable;
use Yiisoft\Queue\Amqp\Adapter;
use Yiisoft\Queue\Amqp\QueueProvider;
use Yiisoft\Queue\Amqp\Settings\Exchange as ExchangeSettings;
use Yiisoft\Queue\Amqp\Settings\QosSettings;
use Yiisoft\Queue\Amqp\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Amqp\Tests\Support\TestMessage as Message;

final class QueueSettingsTest extends UnitTestCase
{
    private const TEST_QUEUE_NAMES = [
        'yii-queue-test-queue-settings-arg',
        'yii-queue-test-queue-common-settings',
    ];

    protected function setUp(): void
    {
        $savedQueueName = $this->queueName;
        $savedExchangeName = $this->exchangeName;

        foreach (self::TEST_QUEUE_NAMES as $name) {
            $this->queueName = $name;
            $this->exchangeName = $name;
            $this->deleteQueue();
            $this->deleteExchange();
        }

        $this->queueName = $savedQueueName;
        $this->exchangeName = $savedExchangeName;

        parent::setUp();
    }

    public function testCommonSettings(): void
    {
        $queueSettings = new QueueSettings(
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
        );

        self::assertTrue($queueSettings->isDurable());
        self::assertTrue($queueSettings->isPassive());
        self::assertTrue($queueSettings->isExclusive());
        self::assertFalse($queueSettings->isAutoDeletable());
        self::assertTrue($queueSettings->hasNowait());
        self::assertNull($queueSettings->getTicket());

        self::assertFalse($queueSettings->withDurable(false)->isDurable());
        self::assertFalse($queueSettings->withPassive(false)->isPassive());
        self::assertFalse($queueSettings->withExclusive(false)->isExclusive());
        self::assertFalse($queueSettings->withAutoDeletable(false)->isAutoDeletable());
        self::assertFalse($queueSettings->withNowait(false)->hasNowait());
        self::assertEquals(0, $queueSettings->withTicket(0)->getTicket());
        self::assertEquals(new QosSettings(prefetchCount: 1), $queueSettings->withQosSettings(new QosSettings(prefetchCount: 1))->getQosSettings());

        self::assertInstanceOf(AMQPTable::class, $queueSettings->getArguments());
        self::assertArrayHasKey('x-dead-letter-exchange', $queueSettings->getArguments());
        self::assertArrayHasKey('x-message-ttl', $queueSettings->getArguments());
        self::assertArrayHasKey('x-expires', $queueSettings->getArguments());
    }

    public function testArgumentsXExpires(): void
    {
        $this->queueName = 'yii-queue-test-queue-settings-arg';
        $this->exchangeName = 'yii-queue-test-queue-settings-arg';

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
            new MessageSerializer(new JsonMessageEncoder()),
            $this->getLoop(),
        );

        $this->getQueueWithAdapter($adapter)
            ->push(
                Message::fromPayload('ext-simple', ['payload' => time()])
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
        self::assertNotSame($queueSettings, $queueSettings->withQosSettings(new QosSettings(prefetchCount: 1)));
    }
}
