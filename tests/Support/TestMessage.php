<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use Yiisoft\Queue\Message\MessageInterface;

final class TestMessage implements MessageInterface
{
    public function __construct(
        private readonly string $type,
        private readonly mixed $data,
        private readonly array $metadata = [],
    ) {
    }

    public static function fromData(string $type, mixed $data, array $metadata = []): self
    {
        return new self($type, $data, $metadata);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(array $metadata): static
    {
        return new self($this->type, $this->data, $metadata);
    }
}
