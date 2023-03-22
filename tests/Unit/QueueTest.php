<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Unit;

use Exception;
use Yiisoft\Yii\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Yii\Queue\AMQP\Tests\Integration\TestCase;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Yii\Queue\Message\Message;

final class QueueTest extends TestCase
{
    /**
     * Testing getting status
     *
     * @throws Exception
     *
     * @return void
     */
    public function testStatus(): void
    {
        $adapter = $this->getAdapter();
        $queue = $this
            ->getQueue()
            ->withAdapter($adapter);

        $message = new Message('ext-simple', null);
        $queue->push(
            $message,
        );

        $this->expectException(NotImplementedException::class);
        $adapter->status($message->getId());
    }

    /**
     * @throws Exception
     *
     * @return void
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
}
