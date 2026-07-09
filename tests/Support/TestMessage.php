<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Support;

use Yiisoft\Queue\Message\MessageInterface;

final class TestMessage implements MessageInterface
{
    /**
     * @param array|bool|float|int|string|null $payload
     * @param array<string, array|bool|float|int|string|null> $meta
     */
    public function __construct(
        private readonly string $type,
        private readonly bool|int|float|string|array|null $payload,
        private readonly array $meta = [],
    ) {
    }

    public static function fromPayload(string $type, bool|int|float|string|array|null $payload): static
    {
        return new self($type, $payload);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): bool|int|float|string|array|null
    {
        return $this->payload;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function withMeta(array $meta): static
    {
        return new self($this->type, $this->payload, $meta);
    }
}
