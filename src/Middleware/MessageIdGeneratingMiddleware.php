<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Middleware;

use Yiisoft\Yii\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;

final class MessageIdGeneratingMiddleware implements MiddlewarePushInterface
{
    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
    {
        $message = $request->getMessage();
        if ($message->getId() === null) {
            $message->setId(uniqid(more_entropy: true));
        }

        return $handler->handlePush($request);
    }
}
