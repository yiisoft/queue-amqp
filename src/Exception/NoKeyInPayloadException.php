<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Exception;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Queue\AMQP\MessageSerializerInterface;

class NoKeyInPayloadException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function __construct(
        protected string $expectedKey,
        protected array $payload,
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct(
            "No expected key '$expectedKey' in payload. Payload's keys list: " .
            implode(', ', array_keys($payload)) .
            '.',
            $code,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'No key "' . $this->expectedKey . '" in payload';
    }

    /**
     * @return string
     *
     * @infection-ignore-all
     */
    public function getSolution(): ?string
    {
        return sprintf(
            "We have successfully unserialized a message, but there was no expected key \"%s\".
        There are the following keys in the message: %s.
        You might want to change message's structure, or make your own implementation of %s,
        which won't rely on this key in the message.",
            $this->expectedKey,
            implode(', ', array_keys($this->payload)),
            MessageSerializerInterface::class
        );
    }
}
