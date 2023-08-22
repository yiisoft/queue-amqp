<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Support;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase as PhpUnitTestCase;

abstract class MainTestCase extends PhpUnitTestCase
{
    public ?string $queueName = 'yii-queue';
    public ?string $exchangeName = 'yii-queue';

    /**
     * @throws Exception
     *
     * @return AMQPStreamConnection
     */
    protected function createConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            getenv('RABBITMQ_HOST'),
            getenv('RABBITMQ_PORT'),
            getenv('RABBITMQ_USER'),
            getenv('RABBITMQ_PASSWORD')
        );
    }

    protected function deleteQueue(): void
    {
        if (null !== $this->queueName) {
            $connection = $this->createConnection();
            $channel = $connection->channel();
            $channel->queue_delete($this->queueName);
        }
    }

    protected function deleteExchange(): void
    {
        if (null !== $this->exchangeName) {
            $connection = $this->createConnection();
            $channel = $connection->channel();
            $channel->exchange_delete($this->exchangeName);
        }
    }
}
