<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Exception;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class ExchangeDeclaredException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'Exchange is declared';
    }

    public function getSolution(): ?string
    {
        return <<<'SOLUTION'
            Can't explicitly set queue name when an exchange is declared.

            Probably, you have called QueueFactory::get() without explicit configuration
            for a given queue.
            Your QueueProvider configuration has an exchange,
            which can't be implicitly binded to a new queue due to differences in behaviors
            of different types of exchanges. Please, create an explicit configuration
            with a fully-configured adapter for the queue you are trying to create.

            Reference: https://github.com/yiisoft/queue/blob/master/docs/guide/en/queue-names.md

            SOLUTION;
    }
}
