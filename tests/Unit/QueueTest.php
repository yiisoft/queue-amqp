<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Exception;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageSerializerInterface;
use Yiisoft\Queue\Queue;

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
        $queue = $this->getDefaultQueue($adapter);

        $message = new Message('ext-simple', null);
        $queue->push($message);

        $status = $adapter->status(IdEnvelope::fromMessage($message)->getId() ?? '');
        self::assertSame(MessageStatus::NOT_FOUND, $status);
    }

    /**
     * @throws Exception
     */
    public function testRun(): void
    {
        $this->queueName = 'yii-test-run';
        $time = time();
        $fileName = 'test-run' . $time;
        $fileHelper = new FileHelper();

        $this->queueSettings = new QueueSettings($this->queueName);

        $queue = $this->getDefaultQueue($this->getAdapter());

        $queue->push(
            new Message('ext-simple', ['file_name' => $fileName, 'payload' => ['time' => $time]])
        );

        self::assertNull($fileHelper->get($fileName));

        $queue->run();

        $result = $fileHelper->get($fileName);
        self::assertNotNull($result);
        self::assertEquals($time, $result);
    }

    public function testListenWithException(): void
    {
        $this->queueName = 'yii-test-exception-listen';
        $this->exchangeName = 'yii-test-exception-listen';

        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueSettings(new QueueSettings($this->queueName))
                ->withExchangeSettings(new ExchangeSettings($this->exchangeName)),
            new JsonMessageSerializer(),
            $this->getLoop(),
        );
        $queue = $this->getDefaultQueue($adapter);

        $time = time();
        $queue->push(new Message('exception-listen', ['payload' => ['time' => $time]]));

        $this->expectException(MessageFailureException::class);

        $queue->listen();

        $this->expectExceptionMessage((string)$time);
    }

    public function testListen(): void
    {
        $time = time();
        $mockLoop = $this->createMock(LoopInterface::class);
        $mockLoop->expects($this->exactly(2))->method('canContinue')->willReturn(true, false);

        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueName('yii-queue'),
            new JsonMessageSerializer(),
            $mockLoop,
        );
        $queue = $this->getDefaultQueue($adapter);

        $queue->push(
            new Message('ext-simple', ['file_name' => 'test-listen' . $time, 'payload' => ['time' => $time]])
        );
        $queue->listen();
    }

    private function getDefaultQueue(AdapterInterface $adapter): Queue
    {
        return $this->makeQueue($adapter);
    }
}
