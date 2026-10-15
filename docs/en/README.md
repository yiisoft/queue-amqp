# Yii Queue AMQP Adapter documentation

- [Message properties](#message-properties)
- [Internals](internals.md)

## Message properties

`Yiisoft\Queue\Amqp\QueueProvider` and `Yiisoft\Queue\Amqp\QueueProviderInterface` are public APIs that you can use in
your application's dependency injection configuration.

To configure default AMQP message properties, override the `QueueProviderInterface` definition in your application's
`di` configuration:

```php
use PhpAmqpLib\Message\AMQPMessage;
use Yiisoft\Queue\Amqp\QueueProvider;
use Yiisoft\Queue\Amqp\QueueProviderInterface;

return [
    QueueProviderInterface::class => [
        'class' => QueueProvider::class,
        '__construct()' => [
            'messageProperties' => [
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ],
        ],
    ],
];
```

This example assumes that your AMQP connection and queue settings are already configured. `messageProperties` is a
constructor argument. Its default value is an empty array. The adapter passes these properties to `AMQPMessage` when
publishing a message. For delayed messages, the adapter overrides `expiration` with the delay in milliseconds and
`delivery_mode` with `AMQPMessage::DELIVERY_MODE_PERSISTENT`.

When configuring a provider directly, you can pass the `messageProperties` constructor argument or call
`withMessageProperties()`. The method returns a new provider with the supplied properties replacing the previous array.
