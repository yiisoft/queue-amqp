<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use JsonException;
use Yiisoft\Yii\Queue\AMQP\Exception\NoKeyInPayloadException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class MessageSerializer implements MessageSerializerInterface
{
    /**
     * @throws JsonException
     */
    public function serialize(MessageInterface $message): string
    {
        $payload = [
            'id' => $message->getId(),
            'name' => $message->getHandlerName(),
            'data' => $message->getData(),
            'meta' => $message->getMetadata(),
        ];

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws JsonException
     */
    public function unserialize(string $value): MessageInterface
    {
        /** @var array $payload */
        $payload = json_decode($value, true, 512, JSON_THROW_ON_ERROR);

        $name = $payload['name'] ?? null;
        if (!is_string($name)) {
            throw new NoKeyInPayloadException('name', $payload);
        }

        return new Message(
            $name,
            $payload['data'] ?? null,
            $payload['meta'] ?? [],
            $payload['id'] ?? null,
        );
    }
}
