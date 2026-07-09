<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Unit;

use Yiisoft\Queue\Amqp\Adapter;
use Yiisoft\Queue\Amqp\Exception\ExchangeDeclaredException;
use Yiisoft\Queue\Amqp\QueueProvider;
use Yiisoft\Queue\Amqp\Settings\Exchange as ExchangeSettings;
use Yiisoft\Queue\Amqp\Settings\ExchangeSettingsInterface;
use Yiisoft\Queue\Amqp\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Amqp\Settings\QueueSettingsInterface;
use Yiisoft\Queue\Amqp\Tests\Support\FileHelper;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Amqp\Tests\Support\TestMessage as Message;

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
            new MessageSerializer(new JsonMessageEncoder()),
            $this->getLoop(),
        );

        $queue = $this->getQueueWithAdapter($adapter);

        $fileHelper = new FileHelper();
        $time = time();
        $queue->push(
            Message::fromPayload('ext-simple', ['file_name' => 'test-with-queue-settings', 'payload' => ['time' => $time]])
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

        $messageBody = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($messageBody['payload']['payload']['time'], $result);
    }

    public function testWithQueueNameExchangeDeclaredException(): void
    {
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );

        $this->expectException(ExchangeDeclaredException::class);
        new Adapter(
            $queueProvider
                ->withQueueSettings(
                    new QueueSettings('yii-queue-test-with-queue-name')
                )
                ->withExchangeSettings(
                    new ExchangeSettings('yii-queue-test-with-queue-name')
                )
                ->withQueueName('yii-queue-test-queue-name'),
            new MessageSerializer(new JsonMessageEncoder()),
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
