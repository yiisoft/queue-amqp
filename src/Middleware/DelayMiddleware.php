<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Middleware;

use InvalidArgumentException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AMQP\Adapter;
use Yiisoft\Queue\AMQP\QueueProviderInterface;
use Yiisoft\Queue\AMQP\Settings\ExchangeSettingsInterface;
use Yiisoft\Queue\AMQP\Settings\QueueSettingsInterface;
use Yiisoft\Queue\Middleware\DelayMiddlewareInterface;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\Request;

final class DelayMiddleware implements MiddlewareInterface, DelayMiddlewareInterface
{
    public function __construct(
        private AdapterInterface $adapter,
        private float $delayInSeconds,
        private bool $forcePersistentMessages = true
    ) {
        if (!$adapter instanceof Adapter) {
            throw new InvalidArgumentException(
                sprintf(
                    'This middleware works only with the %s. %s given.',
                    Adapter::class,
                    get_debug_type($adapter)
                )
            );
        }
    }

    /**
     * @param float $seconds
     *
     * @return $this
     */
    public function withDelay(float $seconds): self
    {
        $new = clone $this;
        $new->delayInSeconds = $seconds;

        return $new;
    }

    public function getDelay(): float
    {
        return $this->delayInSeconds;
    }

    public function process(Request $request, MessageHandlerInterface $handler): Request
    {
        $queueProvider = $this->adapter->getQueueProvider();
        $originalExchangeSettings = $queueProvider->getExchangeSettings();
        $delayedExchangeSettings = $this->getExchangeSettings($originalExchangeSettings);
        $queueSettings = $this->getQueueSettings(
            $queueProvider->getQueueSettings(),
            $originalExchangeSettings
        );

        $adapter = $this->adapter->withQueueProvider(
            $queueProvider
                ->withMessageProperties($this->getMessageProperties($queueProvider))
                ->withExchangeSettings($delayedExchangeSettings)
                ->withQueueSettings($queueSettings)
        );

        return $handler->handle(
            $request->withQueue(
                $request->getQueue()->withAdapter($adapter)
            )
        );
    }

    /**
     * @psalm-return array{expiration: int|float, delivery_mode?: int}&array
     */
    private function getMessageProperties(QueueProviderInterface $queueProvider): array
    {
        $messageProperties = ['expiration' => $this->delayInSeconds * 1000];
        if ($this->forcePersistentMessages === true) {
            $messageProperties['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT;
        }

        return array_merge($queueProvider->getMessageProperties(), $messageProperties);
    }

    private function getQueueSettings(
        QueueSettingsInterface $queueSettings,
        ?ExchangeSettingsInterface $exchangeSettings
    ): QueueSettingsInterface {
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
     *
     * @psalm-suppress LessSpecificReturnType
     */
    private function getExchangeSettings(?ExchangeSettingsInterface $exchangeSettings): ?ExchangeSettingsInterface
    {
        /** @noinspection NullPointerExceptionInspection */
        return $exchangeSettings
            ?->withName("{$exchangeSettings->getName()}.dlx")
            ->withAutoDelete(false)
            ->withType(AMQPExchangeType::TOPIC);
    }
}
