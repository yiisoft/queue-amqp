<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Middleware;

use InvalidArgumentException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Yiisoft\Yii\Queue\AMQP\Adapter;
use Yiisoft\Yii\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Yii\Queue\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Yii\Queue\AMQP\Settings\QueueSettingsInterface;
use Yiisoft\Yii\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;

final class DelayMiddleware implements DelayMiddlewareInterface
{
    public function __construct(private int $delayInSeconds, private bool $forcePersistentMessages = true)
    {
    }

    public function withDelay(float $delayInSeconds): static
    {
        $new = clone $this;
        $new->delayInSeconds = $this->delayInSeconds;

        return $new;
    }

    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
    {
        $adapter = $request->getAdapter();
        if (!$adapter instanceof Adapter) {
            $type = get_debug_type($adapter);
            $class = Adapter::class;
            throw new InvalidArgumentException(
                "This middleware works only with the $class. $type given."
            );
        }

        $queueProvider = $adapter->getQueueProvider();
        $exchangeSettings = $this->getExchangeSettings($queueProvider->getExchangeSettings());
        $queueSettings = $this->getQueueSettings($queueProvider->getQueueSettings(), $queueProvider->getExchangeSettings());
        $adapter = $adapter->withQueueProvider(
            $queueProvider
                ->withMessageProperties($this->getMessageProperties($queueProvider))
                ->withExchangeSettings($exchangeSettings)
                ->withQueueSettings($queueSettings)
        );

        return $handler->handlePush($request->withAdapter($adapter));
    }

    /**
     * @return (int|mixed)[]
     *
     * @psalm-return array{expiration: int, delivery_mode?: int}
     */
    private function getMessageProperties(QueueProviderInterface $queueProvider): array
    {
        $messageProperties = ['expiration' => $this->delayInSeconds * 1000];
        if ($this->forcePersistentMessages === true) {
            $messageProperties['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT;
        }

        return array_merge($queueProvider->getMessageProperties(), $messageProperties);
    }

    private function getQueueSettings(QueueSettingsInterface $queueSettings, ?ExchangeSettingsInterface $exchangeSettings): QueueSettingsInterface
    {
        $deliveryTime = time() + $this->delayInSeconds;

        return $queueSettings
            ->withName("{$queueSettings->getName()}.dlx.$deliveryTime")
            ->withAutoDeletable(true)
            ->withArguments(
                [
                    'x-dead-letter-exchange' => ['S', $exchangeSettings?->getName() ?? ''],
                    'x-expires' => ['I', $this->delayInSeconds * 1000 + 30000],
                    'x-message-ttl' => ['I', $this->delayInSeconds * 1000],
                ]
            );
    }

    /**
     * @see https://github.com/vimeo/psalm/issues/9454
     * @psalm-suppress LessSpecificReturnType
     */
    private function getExchangeSettings(?ExchangeSettingsInterface $exchangeSettings): ?ExchangeSettingsInterface
    {
        /** @noinspection NullPointerExceptionInspection */
        return $exchangeSettings
            ?->withName("{$exchangeSettings->getName()}.dlx")
            ->withAutoDelete(true)
            ->withType(AMQPExchangeType::TOPIC);
    }
}
