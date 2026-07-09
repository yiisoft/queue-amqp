<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Integration;

use Yiisoft\Queue\Amqp\Adapter;
use Yiisoft\Queue\Amqp\QueueProvider;
use Yiisoft\Queue\Amqp\Settings\Queue as QueueSettings;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\Serializer\JsonMessageEncoder;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Amqp\Tests\Support\TestMessage as Message;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConsumeExistingMessagesTest extends TestCase
{
    public function testConsumeExistingMessages(): void
    {
        $loop = new SimpleLoop();
        $serializer = new MessageSerializer(new JsonMessageEncoder());
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
            $adapter->push(Message::fromPayload('test', ['payload' => 'test']));
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
        $serializer = new MessageSerializer(new JsonMessageEncoder());
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
            $adapter->push(Message::fromPayload('test', ['payload' => 'test']));
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
        }

        self::assertEquals($messageCount, $processingCount);
    }
}
