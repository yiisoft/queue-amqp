<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Integration;

use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\AMQP\Tests\Integration\TestCase;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConsumeExistingMessagesTest extends TestCase
{
    public function testConsumeExistingMessages(): void
    {
        $loop = new SimpleLoop();
        $serializer = new JsonMessageSerializer();
        $queueProvider = new QueueProvider(
            new AMQPStreamConnection(
                getenv('RABBITMQ_HOST'),
                getenv('RABBITMQ_PORT'),
                getenv('RABBITMQ_USER'),
                getenv('RABBITMQ_PASSWORD'),
            ),
            new QueueSettings()
        );
        $adapter = new Adapter($queueProvider, $serializer, $loop);

        $messageCount = 10;
        for ($i = 0; $i < $messageCount; $i++) {
            $adapter->push(new Message('test', ['payload' => 'test']));
        }

        // wait for messages to be ready to consume
        sleep(1);

        $processingCount = 0;
        $adapter->runExisting(static function() use (&$processingCount): bool {
            $processingCount++;
            return true;
        });

        self::assertEquals($messageCount, $processingCount);
    }

    public function testConsumeExistingMessagesByOne(): void
    {
        $loop = new SimpleLoop();
        $serializer = new JsonMessageSerializer();
        $queueProvider = new QueueProvider(
            new AMQPStreamConnection(
                getenv('RABBITMQ_HOST'),
                getenv('RABBITMQ_PORT'),
                getenv('RABBITMQ_USER'),
                getenv('RABBITMQ_PASSWORD'),
            ),
            new QueueSettings()
        );
        $adapter = new Adapter($queueProvider, $serializer, $loop);

        $messageCount = 10;
        for ($i = 0; $i < $messageCount; $i++) {
            $adapter->push(new Message('test', ['payload' => 'test']));
        }

        // wait for messages to be ready to consume
        sleep(1);

        $processingCount = 0;
        $messageProcessed = true;

        // Call the `runExisting` method $messageCount times
        while ($messageProcessed) {
            $messageProcessed = false;
            $adapter->runExisting(static function () use (&$processingCount, &$messageProcessed): bool {
                if ($messageProcessed) {
                    return false;
                }

                $messageProcessed = true;
                $processingCount++;

                return true;
            });
        };

        self::assertEquals($messageCount, $processingCount);
    }
}
