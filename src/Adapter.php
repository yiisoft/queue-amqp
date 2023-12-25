<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use PhpAmqpLib\Message\AMQPMessage;
use Throwable;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\AMQP\Exception\NotImplementedException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Message\ParametrizedMessageInterface;

final class Adapter implements AdapterInterface
{
    public function __construct(
        private QueueProviderInterface $queueProvider,
        private MessageSerializerInterface $serializer,
        private LoopInterface $loop,
    ) {
    }

    public function withChannel(string $channel): self
    {
        $instance = clone $this;
        $instance->queueProvider = $this->queueProvider->withChannelName($channel);

        return $instance;
    }

    /**
     * @param callable(MessageInterface): bool  $handlerCallback
     */
    public function runExisting(callable $handlerCallback): void
    {
        $channel = $this->queueProvider->getChannel();
        (new ExistingMessagesConsumer($channel, $this->queueProvider
            ->getQueueSettings()
            ->getName(), $this->serializer))
            ->consume($handlerCallback);
    }

    /**
     * @return never
     */
    public function status(string $id): JobStatus
    {
        throw new NotImplementedException('Status check is not supported by the adapter ' . self::class . '.');
    }

    public function push(MessageInterface $message): void
    {
        $payload = $this->serializer->serialize($message);
        $amqpMessage = new AMQPMessage(
            $payload,
            array_merge(['message_id' => uniqid(more_entropy: true)], $this->queueProvider->getMessageProperties())
        );
        $exchangeSettings = $this->queueProvider->getExchangeSettings();
        $this->queueProvider
            ->getChannel()
            ->basic_publish(
                $amqpMessage,
                $exchangeSettings?->getName() ?? '',
                $exchangeSettings ? '' : $this->queueProvider
                    ->getQueueSettings()
                    ->getName()
            );
        if ($message instanceof ParametrizedMessageInterface) {
            /** @var string $messageId */
            $messageId = $amqpMessage->get('message_id');
            $message->setId($messageId);
        }
    }

    public function subscribe(callable $handlerCallback): void
    {
        $channel = $this->queueProvider->getChannel();
        $channel->basic_consume(
            $this->queueProvider
                ->getQueueSettings()
                ->getName(),
            $this->queueProvider
                ->getQueueSettings()
                ->getName(),
            false,
            false,
            false,
            true,
            function (AMQPMessage $amqpMessage) use ($handlerCallback, $channel): void {
                try {
                    $handlerCallback($this->serializer->unserialize($amqpMessage->body));
                    $channel->basic_ack($amqpMessage->getDeliveryTag());
                } catch (Throwable $exception) {
                    $consumerTag = $amqpMessage->getConsumerTag();
                    if ($consumerTag !== null) {
                        $channel->basic_cancel($consumerTag);
                    }

                    throw $exception;
                }
            }
        );

        while ($this->loop->canContinue()) {
            $channel->wait();
        }
    }

    public function getQueueProvider(): QueueProviderInterface
    {
        return $this->queueProvider;
    }

    public function withQueueProvider(QueueProviderInterface $queueProvider): self
    {
        $new = clone $this;
        $new->queueProvider = $queueProvider;

        return $new;
    }
}
