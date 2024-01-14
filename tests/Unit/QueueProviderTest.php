<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\Exception\ExchangeDeclaredException;

use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Queue\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\AMQP\Settings\QueueSettingsInterface;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;

final class QueueProviderTest extends UnitTestCase
{
    public function testWithQueueAndExchangeSettings(): void
    {
        $this->queueName = 'yii-queue-test-with-queue-settings';
        $this->exchangeName = 'yii-queue-test-with-queue-settings';

        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueSettings(
                    new QueueSettings($this->queueName)
                )
                ->withExchangeSettings(
                    new ExchangeSettings($this->exchangeName)
                ),
            new JsonMessageSerializer(),
            $this->getLoop(),
        );

        $queue = $this->getQueue()->withAdapter($adapter);

        $fileHelper = new FileHelper();
        $time = time();
        $queue->push(
            new Message('ext-simple', ['file_name' => 'test-with-queue-settings', 'payload' => ['time' => $time]])
        );

        $message = $this
            ->createConnection()
            ->channel()
            ->basic_get($this->queueName);
        $message->nack(true);

        self::assertNull($fileHelper->get('test-with-queue-settings'));

        $queue->run();

        $result = $fileHelper->get('test-with-queue-settings');
        self::assertNotNull($result);
        self::assertEquals($time, $result);

        $messageBody = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($messageBody['data']['payload']['time'], $result);
    }

    public function testWithChannelNameExchangeDeclaredException(): void
    {
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );

        $this->expectException(ExchangeDeclaredException::class);
        new Adapter(
            $queueProvider
                ->withQueueSettings(
                    new QueueSettings('yii-queue-test-with-channel-name')
                )
                ->withExchangeSettings(
                    new ExchangeSettings('yii-queue-test-with-channel-name')
                )
                ->withChannelName('yii-queue-test-channel-name'),
            new JsonMessageSerializer(),
            $this->getLoop(),
        );
    }

    public function testImmutable(): void
    {
        $queueSettings = $this->createMock(QueueSettingsInterface::class);
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $queueSettings
        );

        self::assertNotSame($queueProvider, $queueProvider->withQueueSettings($queueSettings));
        self::assertNotSame($queueProvider, $queueProvider->withExchangeSettings($this->createMock(ExchangeSettingsInterface::class)));
        self::assertNotSame($queueProvider, $queueProvider->withMessageProperties([]));
    }
}
