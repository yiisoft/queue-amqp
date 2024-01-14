<?php

declare(strict_types=1);

namespace Yiisoft\Queue\AMQP\Tests\Support;

use RuntimeException;

final class FileHelper
{
    /**
     * @param string $fileName File to be created
     * @param int|string $data Data to be written into the file
     */
    public function put(string $fileName, int|string $data): void
    {
        $path = $this->getRuntimeDir() . DIRECTORY_SEPARATOR . $fileName;
        if (file_put_contents($path, $data) === false) {
            throw new RuntimeException(
                sprintf(
                    'Runtime dir %"s" or file "%s" are not writable.',
                    $this->getRuntimeDir(),
                    $fileName
                )
            );
        }
    }

    public function get(string $filename): ?string
    {
        $path = $this->getFilePath($filename);
        if (!file_exists($path)) {
            return null;
        }

        $result = file_get_contents($path);
        if ($result === false) {
            throw new RuntimeException(
                sprintf(
                    'File "%s" exists but is not readable.',
                    $path
                )
            );
        }

        return $result;
    }

    public function getFilePath(string $filename): string
    {
        return "{$this->getRuntimeDir()}/$filename";
    }

    public function clear(): void
    {
        $files = glob("{$this->getRuntimeDir()}/*");
        foreach ($files as $file) {
            if (is_file($file) && !str_ends_with($file, '.log')) {
                unlink($file);
            }
        }
    }

    private function getRuntimeDir(): string
    {
        return dirname(__DIR__) . '/runtime';
    }
}
