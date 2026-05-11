<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Settings;

use InvalidArgumentException;

/**
 * Quality of Service settings for AMQP `basic.qos`.
 *
 * Instructs the broker how many messages (and how many bytes) it may push to a consumer
 * before the consumer must acknowledge at least one of them. Without QoS, the broker
 * dispatches all available messages to the first ready consumer, leaving other workers
 * idle — a problem especially visible under high load with slow handlers.
 *
 * Typical production setting: `new QosSettings(prefetchCount: 1)` gives true round-robin
 * distribution — each worker receives one message at a time and gets the next only after
 * acknowledging the previous one.
 *
 * Note: RabbitMQ does not enforce `prefetchSize` (it is accepted but ignored). Only
 * `prefetchCount` has a practical effect in RabbitMQ.
 *
 * @see https://www.rabbitmq.com/docs/consumer-prefetch RabbitMQ Consumer Prefetch
 * @see https://www.rabbitmq.com/amqp-0-9-1-reference#basic.qos AMQP 0-9-1 basic.qos reference
 */
final class QosSettings
{
    /**
     * @param int $prefetchSize  Maximum number of octets the broker may hold in-flight per consumer
     *                           (i.e. sent but not yet acknowledged). The broker will not send a new
     *                           message if doing so would exceed this byte budget.
     *                           0 disables the byte-level limit entirely.
     *                           Note: RabbitMQ accepts this field but does not enforce it; only
     *                           {@see self::$prefetchCount} has a practical effect.
     * @param int $prefetchCount Maximum number of unacknowledged messages the broker may deliver
     *                           before waiting for at least one acknowledgement.
     *                           0 disables the message-count limit (unlimited prefetch — the default
     *                           AMQP behaviour, and the source of worker starvation in multi-consumer
     *                           setups). Set to 1 for strict one-at-a-time round-robin distribution.
     * @param bool $global       Scope of the limits. The AMQP 0-9-1 specification defines global=true
     *                           as connection-wide and global=false as channel-wide, but RabbitMQ
     *                           redefines the semantics: global=false (default) applies the limit
     *                           per consumer registered on the channel; global=true applies it to
     *                           all consumers sharing the channel.
     *
     * @psalm-param non-negative-int $prefetchSize
     * @psalm-param non-negative-int $prefetchCount
     *
     * @throws InvalidArgumentException if $prefetchSize or $prefetchCount is negative.
     *
     * @see https://www.rabbitmq.com/docs/consumer-prefetch RabbitMQ Consumer Prefetch
     * @see https://www.rabbitmq.com/amqp-0-9-1-reference#basic.qos AMQP 0-9-1 basic.qos reference
     */
    public function __construct(
        private readonly int $prefetchSize = 0,
        private readonly int $prefetchCount = 0,
        private readonly bool $global = false,
    ) {
        /**
         * @psalm-suppress DocblockTypeContradiction
         * @psalm-suppress InvalidCast
         * Runtime guard for callers without static analysis.
         */
        if ($prefetchSize < 0) {
            throw new InvalidArgumentException(
                "Prefetch size must be a non-negative integer, $prefetchSize given."
            );
        }
        /**
         * @psalm-suppress DocblockTypeContradiction
         * @psalm-suppress InvalidCast
         * Runtime guard for callers without static analysis.
         */
        if ($prefetchCount < 0) {
            throw new InvalidArgumentException(
                "Prefetch count must be a non-negative integer, $prefetchCount given."
            );
        }
    }

    /**
     * Returns the maximum number of octets the broker may hold unacknowledged per consumer.
     * 0 means no byte-level limit.
     *
     * Note: RabbitMQ accepts this value but does not enforce it.
     *
     * @psalm-return non-negative-int
     *
     * @see https://www.rabbitmq.com/amqp-0-9-1-reference#basic.qos.prefetch-size AMQP 0-9-1 prefetch-size field
     */
    public function getPrefetchSize(): int
    {
        return $this->prefetchSize;
    }

    /**
     * Returns the maximum number of unacknowledged messages the broker may deliver before waiting
     * for an acknowledgement. 0 means unlimited.
     *
     * @psalm-return non-negative-int
     *
     * @see https://www.rabbitmq.com/amqp-0-9-1-reference#basic.qos.prefetch-count AMQP 0-9-1 prefetch-count field
     */
    public function getPrefetchCount(): int
    {
        return $this->prefetchCount;
    }

    /**
     * Returns whether the QoS limits apply globally to the channel (true) or per consumer (false).
     *
     * RabbitMQ redefines the AMQP 0-9-1 semantics of this field: false means the limit applies
     * to each individual consumer registered on the channel; true means the limit is shared across
     * all consumers on the channel.
     *
     * @see https://www.rabbitmq.com/docs/consumer-prefetch#per-channel-vs-per-consumer RabbitMQ per-channel vs per-consumer prefetch
     * @see https://www.rabbitmq.com/amqp-0-9-1-reference#basic.qos.global AMQP 0-9-1 global field
     */
    public function isGlobal(): bool
    {
        return $this->global;
    }
}
