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
use Yiisoft\Yii\Queue\AMQP\Tests\Integration\TestCase;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Yii\Queue\Exception\JobFailureException;
use Yiisoft\Yii\Queue\Message\Message;

final class QueueTest extends TestCase
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
        $this->expectExceptionMessage("Status check is not supported by the adapter $adapterClass");
        $adapter->status($message->getId());
    }

    /**
     * @throws Exception
     */
    public function testRun(): void
    {
        $fileHelper = new FileHelper();
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());

        $time = time();
        $queue->push(
            new Message('ext-simple', ['file_name' => 'test-run', 'payload' => ['time' => $time]])
        );

        self::assertNull($fileHelper->get('test-run'));

        $queue->run();

        $result = $fileHelper->get('test-run');
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
