<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Exception;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class NoKeyInPayloadException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    protected string $expectedKey;
    protected array $payload;

    public function __construct(string $expectedKey, array $payload, int $code = 0, Throwable $previous = null)
    {
        $this->expectedKey = $expectedKey;
        $this->payload = $payload;
        parent::__construct("No expected key '$expectedKey' in payload. Payload's keys list: " . implode(
            ', ',
            array_keys($payload)
        ) . '.', $code, $previous);
    }

    public function getName(): string
    {
        return 'No key "' . $this->expectedKey . '" in payload';
    }

    public function getSolution(): ?string
    {
        return 'We have successfully unserialized a message, but there was no expected key "' . $this->expectedKey . '".
        There are the following keys in the message: ' . implode(', ', array_keys($this->payload)) . '.
        You might want to change message\'s structure, or make your own implementation of \\Yiisoft\\Yii\\Queue\\AMQP\\MessageSerializerInterface,
        which won\'t rely on this key in the message.';
    }
}
