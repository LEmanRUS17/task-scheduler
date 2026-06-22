<?php

declare(strict_types=1);

namespace App\Tests\Unit\FileFeature\Infrastructure;

use App\FileFeature\Infrastructure\Storage\LocalFileStorage;
use PHPUnit\Framework\TestCase;

final class LocalFileStorageTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/file-storage-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->baseDir)) {
            $this->removeRecursively($this->baseDir);
        }
    }

    public function testStoreMovesFileAndCreatesNestedDirectories(): void
    {
        $storage = new LocalFileStorage($this->baseDir);
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        self::assertIsString($tmp);
        file_put_contents($tmp, 'hello');

        $storage->store($tmp, 'avatar/2026/06/abc.png');

        $stored = $this->baseDir . '/avatar/2026/06/abc.png';
        self::assertFileExists($stored);
        self::assertSame('hello', file_get_contents($stored));
        self::assertFileDoesNotExist($tmp);
        self::assertSame($stored, $storage->absolutePath('avatar/2026/06/abc.png'));
    }

    public function testDeleteRemovesStoredFile(): void
    {
        $storage = new LocalFileStorage($this->baseDir);
        $tmp = tempnam(sys_get_temp_dir(), 'upl');
        self::assertIsString($tmp);
        file_put_contents($tmp, 'data');
        $storage->store($tmp, 'attachment/file.bin');

        $storage->delete('attachment/file.bin');

        self::assertFileDoesNotExist($this->baseDir . '/attachment/file.bin');
    }

    private function removeRecursively(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
