<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use InvalidArgumentException;
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
     * @throws NoKeyInPayloadException
     * @throws InvalidArgumentException
     */
    public function unserialize(string $value): Message
    {
        $payload = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            /** @infection-ignore-all */
            throw new InvalidArgumentException('Payload must be array. Got ' . get_debug_type($payload) . '.');
        }

        $name = $payload['name'] ?? null;
        if (!is_string($name)) {
            throw new NoKeyInPayloadException('name', $payload);
        }

        $id = $payload['id'] ?? null;
        if ($id !== null && !is_string($id)) {
            throw new NoKeyInPayloadException('id', $payload);
        }

        $meta = $payload['meta'] ?? [];
        if (!is_array($meta)) {
            throw new NoKeyInPayloadException('meta', $payload);
        }

        return new Message(
            $name,
            $payload['data'] ?? null,
            $meta,
            $id,
        );
    }
}
