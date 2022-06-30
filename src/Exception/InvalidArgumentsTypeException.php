<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Exception;

use InvalidArgumentException;
use PhpAmqpLib\Wire\AMQPTable;

class InvalidArgumentsTypeException extends InvalidArgumentException
{
    protected $message = '"arguments" parameter must be either an array or an ' . AMQPTable::class . ' object.';
}
