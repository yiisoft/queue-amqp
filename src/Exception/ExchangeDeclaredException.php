<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Exception;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class ExchangeDeclaredException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    protected $message = 'Can\'t set channel name implicitly when an exchange is declared';

    public function getName(): string
    {
        return 'Exchange is declared';
    }

    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
            Can't explicitly set channel name when an exchange is declared.

            Probably, you have called QueueFactory::get() without explicit configuration
            for a given channel.
            Your QueueProvider configuration has an exchange,
            which can't be implicitly binded to a new queue due to differences in behaviors
            of different types of exchanges. Please, create an explicit configuration
            with a fully-configured adapter for the channel you are trying to create.

            Reference: https://github.com/yiisoft/yii-queue#different-queue-channels

            SOLUTION;
    }
}
