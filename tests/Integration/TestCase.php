<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Integration;

use Symfony\Component\Process\Process;
use Yiisoft\Queue\AMQP\Tests\Support\FileHelper;
use Yiisoft\Queue\AMQP\Tests\Support\MainTestCase;

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
            $command[] = "--channel=$queue";
        }
        $process = new Process($command);
        $this->processes[] = $process;
        $process->start();
    }
}
