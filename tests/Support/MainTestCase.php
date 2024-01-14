<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;

abstract class MainTestCase extends TestCase
{
    public ?string $queueName = 'yii-queue';
    public ?string $exchangeName = 'yii-queue';

    protected function createConnection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            getenv('RABBITMQ_HOST') ?: '127.0.0.1',
            getenv('RABBITMQ_PORT') ?: 5672,
            getenv('RABBITMQ_USER') ?: 'guest',
            getenv('RABBITMQ_PASSWORD') ?: 'guest',
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
