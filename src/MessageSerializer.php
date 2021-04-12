<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP;

use InvalidArgumentException;
use Yiisoft\Factory\Factory;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;

class MessageSerializer implements MessageSerializerInterface
{
    private SerializerInterface $serializer;
    private Factory $factory;

    public function __construct(SerializerInterface $serializer, Factory $factory)
    {
        $this->serializer = $serializer;
        $this->factory = $factory;
    }

    public function serialize(MessageInterface $message): string
    {
        $payload = [
            'name' => $message->getName(),
            'data' => $message->getData(),
            'behaviors' => [],
        ];
        foreach ($message->getBehaviors() as $behavior) {
            $payload['behaviors'][] = [
                'class' => get_class($behavior),
                '__construct()' => $behavior->getConstructorParameters(),
            ];
        }

        return $this->serializer->serialize($payload);
    }

    public function unserialize(string $value): MessageInterface
    {
        $payload = $this->serializer->unserialize($value);

        $name = $payload['name'] ?? null;
        if (!is_string($name)) {
            throw new InvalidArgumentException('Serialized data must specify message name');
        }

        $message = new Message($name, $payload['data'] ?? null);
        foreach ($payload['behaviors'] as $behavior) {
            $message->attachBehavior($this->factory->create($behavior));
        }

        return $message;
    }
}
