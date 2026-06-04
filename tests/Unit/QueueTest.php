<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Unit;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Queue\AMQP\QueueProvider;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\AMQP\Settings\Exchange as ExchangeSettings;
use Yiisoft\Queue\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Queue\AMQP\Settings\QosSettings;
use Yiisoft\Queue\AMQP\Settings\Queue as QueueSettings;
use Yiisoft\Queue\AMQP\Settings\QueueSettingsInterface;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\DelayEnvelope;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\AMQP\Tests\Support\TestMessage as Message;
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
        $adapterClass = $adapter::class;

        $queue = $this->getDefaultQueue($adapter);

        $message = Message::fromData('ext-simple', null);
        $queue->push(
            $message,
        );

        $this->expectException(NotImplementedException::class);
        $this->expectExceptionMessage("Status check is not supported by the adapter $adapterClass.");
        $adapter->status(IdEnvelope::fromMessage($message)->getId() ?? '');
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
            Message::fromData('ext-simple', ['file_name' => $fileName, 'payload' => ['time' => $time]])
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
        $queue->push(Message::fromData('exception-listen', ['payload' => ['time' => $time]]));

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
            Message::fromData('ext-simple', ['file_name' => 'test-listen' . $time, 'payload' => ['time' => $time]])
        );
        $queue->listen();
    }

    private function getDefaultQueue(AdapterInterface $adapter): Queue
    {
        return $this->getQueueWithAdapter($adapter);
    }

    public function testImmutable(): void
    {
        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $adapter = new Adapter(
            $queueProvider,
            $this->createMock(MessageSerializerInterface::class),
            $this->createMock(LoopInterface::class)
        );

        self::assertNotSame($adapter, $adapter->withChannel('test'));
        self::assertNotSame($adapter, $adapter->withQueueProvider($queueProvider));
    }

    public function testPushUsesStringExpirationForDelayedMessage(): void
    {
        $message = new DelayEnvelope(Message::fromData('ext-simple', null), 1.5);
        $exchangeSettings = new ExchangeSettings('test-exchange');
        $queueSettings = new QueueSettings('test-queue');
        $delayMessageProperties = [
            'expiration' => '1500',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        $channel = $this->createMock(AMQPChannel::class);
        $channel
            ->expects(self::once())
            ->method('basic_publish')
            ->with(
                self::callback(static function (AMQPMessage $amqpMessage): bool {
                    self::assertSame('1500', $amqpMessage->get('expiration'));
                    self::assertSame(AMQPMessage::DELIVERY_MODE_PERSISTENT, $amqpMessage->get('delivery_mode'));
                    self::assertSame('payload', $amqpMessage->getBody());

                    return true;
                }),
                'test-exchange.dlx',
                '',
            );

        $delayedQueueProvider = $this->createMock(QueueProviderInterface::class);
        $delayedQueueProvider
            ->method('getMessageProperties')
            ->willReturn($delayMessageProperties);
        $delayedQueueProvider
            ->expects(self::once())
            ->method('withExchangeSettings')
            ->with(self::callback(static function (ExchangeSettingsInterface $settings): bool {
                self::assertSame('test-exchange.dlx', $settings->getName());
                self::assertSame(AMQPExchangeType::TOPIC, $settings->getType());
                self::assertTrue($settings->isAutoDelete());

                return true;
            }))
            ->willReturnSelf();
        $delayedQueueProvider
            ->expects(self::once())
            ->method('withQueueSettings')
            ->with(self::callback(static function (QueueSettingsInterface $settings): bool {
                self::assertMatchesRegularExpression('/^test-queue\.dlx\.\d+$/', $settings->getName());
                self::assertTrue($settings->isAutoDeletable());
                self::assertSame(
                    [
                        'x-dead-letter-exchange' => ['S', 'test-exchange'],
                        'x-expires' => ['I', 31500],
                        'x-message-ttl' => ['I', 1500],
                    ],
                    $settings->getArguments(),
                );

                return true;
            }))
            ->willReturnSelf();
        $delayedQueueProvider
            ->method('getChannel')
            ->willReturn($channel);
        $delayedQueueProvider
            ->method('getExchangeSettings')
            ->willReturn(new ExchangeSettings('test-exchange.dlx'));

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider
            ->method('getMessageProperties')
            ->willReturn([]);
        $queueProvider
            ->method('getExchangeSettings')
            ->willReturn($exchangeSettings);
        $queueProvider
            ->method('getQueueSettings')
            ->willReturn($queueSettings);
        $queueProvider
            ->expects(self::once())
            ->method('withMessageProperties')
            ->with($delayMessageProperties)
            ->willReturn($delayedQueueProvider);

        $serializer = $this->createMock(MessageSerializerInterface::class);
        $serializer
            ->expects(self::once())
            ->method('serialize')
            ->with($message)
            ->willReturn('payload');

        $adapter = new Adapter(
            $queueProvider,
            $serializer,
            $this->createMock(LoopInterface::class)
        );

        self::assertSame($message, $adapter->push($message));
    }

    public function testSubscribeUsesConfiguredBasicQos(): void
    {
        $queueSettings = $this->createMock(QueueSettingsInterface::class);
        $queueSettings->method('getQosSettings')->willReturn(new QosSettings(1024, 10, true));
        $queueSettings->method('getName')->willReturn('test-queue');

        $channel = $this->createMock(AMQPChannel::class);
        $channel->expects(self::once())
            ->method('basic_qos')
            ->with(1024, 10, true);
        $channel->expects(self::once())
            ->method('basic_consume')
            ->with('test-queue', 'test-queue', false, false, false, true, self::anything());

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->method('getChannel')->willReturn($channel);
        $queueProvider->method('getQueueSettings')->willReturn($queueSettings);

        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturn(false);

        $adapter = new Adapter($queueProvider, $this->createMock(MessageSerializerInterface::class), $loop);
        $adapter->subscribe(static fn() => null);
    }

    public function testSubscribeSkipsBasicQosWhenNotConfigured(): void
    {
        $queueSettings = $this->createMock(QueueSettingsInterface::class);
        $queueSettings->method('getQosSettings')->willReturn(null);
        $queueSettings->method('getName')->willReturn('test-queue');

        $channel = $this->createMock(AMQPChannel::class);
        $channel->expects(self::never())->method('basic_qos');
        $channel->expects(self::once())
            ->method('basic_consume')
            ->with('test-queue', 'test-queue', false, false, false, true, self::anything());

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->method('getChannel')->willReturn($channel);
        $queueProvider->method('getQueueSettings')->willReturn($queueSettings);

        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturn(false);

        $adapter = new Adapter($queueProvider, $this->createMock(MessageSerializerInterface::class), $loop);
        $adapter->subscribe(static fn() => null);
    }
}
