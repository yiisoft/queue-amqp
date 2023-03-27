<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Integration\TestCase;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Yii\Queue\Message\Message;

final class QueueProviderTest extends TestCase
{
    public function testWithQueueAndExchangeSettings(): void
    {
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueSettings(
                    new QueueSettings('yii-queue-test-with-queue-settings')
                )
                ->withExchangeSettings(
                    new ExchangeSettings('yii-queue-test-with-queue-settings')
                ),
            new MessageSerializer(),
            $this->getLoop(),
        );

        $queue = $this->getQueue()->withAdapter($adapter);

        $fileHelper = new FileHelper();
        $time = time();
        $queue->push(
            new Message('ext-simple', ['file_name' => 'test-with-queue-settings', 'payload' => ['time' => $time]])
        );

        $amqpConnection = $this->createConnection();
        $channel = $amqpConnection->channel();
        $message = $channel->basic_get(queue: 'yii-queue-test-with-queue-settings');
        $message->nack(true);

        self::assertNull($fileHelper->get('test-with-queue-settings'));

        $queue->run();

        $result = $fileHelper->get('test-with-queue-settings');
        self::assertNotNull($result);
        self::assertEquals($time, $result);

        $messageBody = json_decode($message->body, true, 512, JSON_THROW_ON_ERROR);
        self::assertEquals($messageBody['data']['payload']['time'], $result);
    }
}
