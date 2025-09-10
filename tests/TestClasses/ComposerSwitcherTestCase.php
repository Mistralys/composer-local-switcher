<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Tests\TestClasses;

use FilesystemIterator;
use Mistralys\ComposerSwitcher\ConfigSwitcher;
use Mistralys\ComposerSwitcher\Utils\ConfigFile;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

abstract class ComposerSwitcherTestCase extends TestCase
{
    /**
     * @var string
     */
    protected $assetsFolder;

    /**
     * @var string
     */
    protected $testSource;

    /**
     * @var string
     */
    protected $testTarget;

    /**
     * @var int
     */
    private static $testCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        self::$testCounter++;

        $this->keepWorkFiles = false;
        $this->assetsFolder = __DIR__ . '/../assets';

        $this->testSource = $this->assetsFolder . '/test-project';
        $this->testTarget = $this->assetsFolder . '/work-projects/'.date('YmdHi').'-'.self::$testCounter;

        $this->copyDirectory($this->testSource, $this->testTarget);
    }

    /**
     * @var bool
     */
    private $keepWorkFiles = false;

    protected function setKeepWorkFiles(bool $keep=true) : void
    {
        $this->keepWorkFiles = $keep;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if(!$this->keepWorkFiles && !$this->hasFailed()) {
            $this->removeDirectory($this->testTarget);
        } else {
            echo PHP_EOL;
            echo sprintf("Test target retained for inspection: %s", basename($this->testTarget));
            echo PHP_EOL;
        }
    }

    protected function createSwitcher() : ConfigSwitcher
    {
        return (new ConfigSwitcher(
            new ConfigFile($this->testTarget . '/composer.json'),
            new ConfigFile($this->testTarget . '/composer/composer-prod.json'),
            new ConfigFile($this->testTarget . '/composer/dev-config.json')
        ))
            ->setWriteToConsole(true);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item)
        {
            /* @var $item SplFileInfo */

            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }

    private function copyDirectory(string $src, string $dst): void
    {
        if(!is_dir($dst)) {
            mkdir($dst, 0777, true);
        }

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        /* @var $items RecursiveDirectoryIterator */

        foreach ($items as $item)
        {
            $targetPath = $dst . DIRECTORY_SEPARATOR . $items->getSubPathName();

            if ($item->isDir()) {
                mkdir($targetPath, 0777, true);
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }
}
