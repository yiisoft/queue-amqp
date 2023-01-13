<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\AMQP\Tests\Integration;

use PHPUnit\Framework\TestCase as PhpUnitTestCase;
use Symfony\Component\Process\Process;
use Yiisoft\Yii\Queue\AMQP\Tests\Support\FileHelper;

abstract class TestCase extends PhpUnitTestCase
{
    /** @var Process[] */
    private array $processes = [];

    protected function tearDown(): void
    {
        foreach ($this->processes as $process) {
            $process->stop();
        }
        $this->processes = [];

        (new FileHelper())->clear();

        parent::tearDown();
    }

    protected function queueListen(?string $queue = null): void
    {
        $command = [PHP_BINARY, dirname(__DIR__) . '/yii', 'listen'];
        if ($queue !== null) {
            $command[] = "--channel=$queue";
        }
        $process = new Process($command);
        $this->processes[] = $process;
        $process->start();
    }
}