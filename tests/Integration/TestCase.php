<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Amqp\Tests\Integration;

use Symfony\Component\Process\Process;
use Yiisoft\Queue\Amqp\Tests\Support\FileHelper;
use Yiisoft\Queue\Amqp\Tests\Support\MainTestCase;
use Exception;

use function dirname;

use const PHP_BINARY;

abstract class TestCase extends MainTestCase
{
    /** @var Process[] */
    private array $processes = [];

    protected function setUp(): void
    {
        $this->deleteQueue();
        $this->deleteExchange();

        parent::setUp();

        (new FileHelper())->clear();
    }

    protected function tearDown(): void
    {
        foreach ($this->processes as $process) {
            $process->stop();
        }
        $this->processes = [];

        (new FileHelper())->clear();

        $this->deleteQueue();
        $this->deleteExchange();

        parent::tearDown();
    }

    protected function queueListen(?string $queue = null): void
    {
        // TODO Fail test on subprocess error exit code
        $command = [PHP_BINARY, dirname(__DIR__) . '/yii', 'queue:listen'];
        if ($queue !== null) {
            $command[] = $queue;
        }
        $process = new Process($command);
        $this->processes[] = $process;
        $process->start();

        usleep(500000);
        if (!$process->isRunning()) {
            throw new Exception('Failed to start queue listener process');
        }
    }
}
