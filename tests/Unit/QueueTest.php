<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Exception;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Yii\Queue\Exception\JobFailureException;
use Yiisoft\Yii\Queue\Message\Message;

final class QueueTest extends UnitTestCase
{
    /**
     * Testing getting status
     *
     * @throws Exception
     */
    public function testStatus(): void
    {
        $adapter = $this->getAdapter();
        $adapterClass = $adapter::class;

        $queue = $this
            ->getQueue()
            ->withAdapter($adapter);

        $message = new Message('ext-simple', null);
        $queue->push(
            $message,
        );

        $this->expectException(NotImplementedException::class);
        $this->expectExceptionMessage("Status check is not supported by the adapter $adapterClass.");
        $adapter->status($message->getId());
    }

    /**
     * @throws Exception
     */
    public function testRun(): void
    {
        $time = time();
        $fileName = 'test-run'.$time;
        $fileHelper = new FileHelper();
        $queue = $this
            ->getQueue()->withChannelName('yii-test-run'.$time)
            ->withAdapter($this->getAdapter());

        $queue->push(
            new Message('ext-simple', ['file_name' => $fileName, 'payload' => ['time' => $time]])
        );

        self::assertNull($fileHelper->get($fileName));

        $queue->run();

        $result = $fileHelper->get($fileName);
        self::assertNotNull($result);
        self::assertEquals($time, $result);
    }

    public function testListen(): void
    {
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueSettings(new QueueSettings('yii-queue-test-listen'))
                ->withExchangeSettings(new ExchangeSettings('yii-queue-test-listen')),
            new MessageSerializer(),
            $this->getLoop(),
        );
        $queue = $this
            ->getQueue()
            ->withAdapter($adapter);

        $time = time();
        $queue->push(new Message('simple-listen', ['payload' => ['time' => $time]]));

        $this->expectException(JobFailureException::class);

        $queue->listen();

        $this->expectExceptionMessage((string)$time);
    }
}
