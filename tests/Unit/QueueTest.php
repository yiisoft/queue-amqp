<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Exception;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Yii\Queue\AMQP\MessageSerializer;
use Yiisoft\Yii\Queue\AMQP\QueueProvider;
use Yiisoft\Yii\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Yii\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Exception\JobFailureException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Queue;

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

        $queue = $this->getDefaultQueue($adapter);

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
        $fileName = 'test-run' . $time;
        $fileHelper = new FileHelper();

        $this->queueSettings = new QueueSettings('yii-test-run');

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
        $queueProvider = new QueueProvider(
            $this->createConnection(),
            $this->getQueueSettings(),
        );
        $adapter = new Adapter(
            $queueProvider
                ->withQueueSettings(new QueueSettings('yii-test-exception-listen'))
                ->withExchangeSettings(new ExchangeSettings('yii-test-exception-listen')),
            new MessageSerializer(),
            $this->getLoop(),
        );
        $queue = $this->getDefaultQueue($adapter);

        $time = time();
        $queue->push(new Message('exception-listen', ['payload' => ['time' => $time]]));

        $this->expectException(JobFailureException::class);

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
                ->withChannelName('yii-queue'),
            new MessageSerializer(),
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
        return $this
            ->getQueue()
            ->withAdapter($adapter);
    }
}
