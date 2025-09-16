<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\TestSuites;

use Mistralys\ComposerSwitcher\ConfigSwitcher;
use Mistralys\ComposerSwitcher\Tests\TestClasses\ComposerSwitcherTestCase;
use Mistralys\ComposerSwitcher\Utils\ConfigFile;
use Mistralys\ComposerSwitcher\Utils\LockFile;

final class TestSwitching extends ComposerSwitcherTestCase
{
    // region: _Tests

    /**
     * No switch has been made yet: The status file does not exist.
     */
    public function test_initialState() : void
    {
        //$this->setKeepWorkFiles();

        $switcher = $this->createSwitcher();
        $status = $switcher->getStatus();

        $this->assertFalse($status->exists());
        $this->assertNull($status->getDate());
        $this->assertNull($status->getMode());
        $this->assertFalse($status->isDEV());
        $this->assertFalse($status->isPROD());
        $this->assertEmpty($status->getData());

        $this->assertFalse($switcher->getFlagFile(ConfigSwitcher::MODE_DEV)->exists());
        $this->assertFalse($switcher->getFlagFile(ConfigSwitcher::MODE_PROD)->exists());
    }

    /**
     * No switch has been made yet, so switching to DEV
     * has the following tasks:
     *
     * - Create the status file, marking the mode as DEV.
     * - Create the `composer-prod.json` file (copy from `composer.json`).
     * - Create the `composer-prod.lock` file (copy from `composer.lock`).
     * - Update the `composer.json` file repositories.
     *
     * Expected file structure after this operation:
     *
     * - `composer.json` (DEV configuration, with DEV repositories)
     * - `composer.lock` (NONE, as the DEV repositories have not been installed)
     * - `composer/composer-prod.json` (copy of the original `composer.json`)
     * - `composer/composer-prod.lock` (copy of the original `composer.lock`)
     */
    public function test_initialSwitchToDEV() : void
    {
        //$this->setKeepWorkFiles();

        $switcher = $this->createSwitcher();

        $switcher->switchToDevelopment();

        $status = $switcher->getStatus();
        $this->assertTrue($status->exists());
        $this->assertNotNull($status->getDate());
        $this->assertEquals(ConfigSwitcher::MODE_DEV, $status->getMode());
        $this->assertTrue($status->isDEV());
        $this->assertFalse($status->isPROD());

        $this->assertTrue($switcher->getProdFile()->exists());
        $this->assertTrue($switcher->getProdFile()->getLockFile()->exists());
        $this->assertFalse($switcher->getMainFile()->getLockFile()->exists());

        $this->assertConfigHasExpectedPaths($switcher);
        $this->assertLockFileIsPROD($switcher->getProdFile());
        $this->assertFlagIsDEV($switcher);
    }

    /**
     * No switch has been made yet, so switching to PROD
     * must only create the missing production files.
     */
    public function test_initialSwitchToPROD() : void
    {
        //$this->setKeepWorkFiles();

        $switcher = $this->createSwitcher();

        $switcher->switchToProduction();

        $status = $switcher->getStatus();
        $this->assertTrue($status->exists());
        $this->assertNotNull($status->getDate());
        $this->assertEquals(ConfigSwitcher::MODE_PROD, $status->getMode());
        $this->assertTrue($status->isPROD());
        $this->assertFalse($status->isDEV());

        // The main composer.json file has been copied to the
        // production file, and the lock file has been copied.
        $this->assertTrue($switcher->getProdFile()->exists());
        $this->assertTrue($switcher->getProdFile()->getLockFile()->exists());

        $this->assertLockFileIsPROD($switcher->getMainFile());
        $this->assertFlagIsPROD($switcher);
    }

    public function test_switchDEVToPROD() : void
    {
        //$this->setKeepWorkFiles();

        $switcher = $this->createSwitcher();
        $switcher->switchToDevelopment();

        // Simulate the user creating the DEV lock file
        $this->assertNotFalse(file_put_contents(
            $switcher->getMainFile()->getLockFile()->getPath(),
            'DEV'
        ));

        $switcher->switchToProduction();

        $this->assertLockFileIsPROD($switcher->getMainFile());
        $this->assertLockFileIsDEV($switcher->getDevFile());
        $this->assertFlagIsPROD($switcher);
    }

    public function test_specificPackageVersion() : void
    {
        $switcher = $this->createSwitcher();
        $switcher->switchToDevelopment();

        $this->assertConfigHasCustomVersion($switcher, 'mistralys/application-utils-core', '2.3.14');
    }

    // endregion

    // region: Support methods

    private function assertFlagIsDEV(ConfigSwitcher $switcher) : void
    {
        $this->assertTrue($switcher->getFlagFile(ConfigSwitcher::MODE_DEV)->exists());
        $this->assertFalse($switcher->getFlagFile(ConfigSwitcher::MODE_PROD)->exists());
    }

    private function assertFlagIsPROD(ConfigSwitcher $switcher) : void
    {
        $this->assertFalse($switcher->getFlagFile(ConfigSwitcher::MODE_DEV)->exists());
        $this->assertTrue($switcher->getFlagFile(ConfigSwitcher::MODE_PROD)->exists());
    }
    
    /**
     * @param ConfigFile|LockFile $target
     * @return void
     */
    private function assertLockFileIsDEV($target) : void
    {
        $this->assertLockFileIs($target, 'DEV');
    }

    /**
     * @param ConfigFile|LockFile $target
     * @return void
     */
    private function assertLockFileIsPROD($target) : void
    {
        $this->assertLockFileIs($target, 'PROD');
    }

    /**
     * @param ConfigFile|LockFile $target
     * @return void
     */
    private function assertLockFileIs($target, string $mode) : void
    {
        if($target instanceof ConfigFile) {
            $target = $target->getLockFile();
        }

        $this->assertSame($mode, $target->getContent());
    }

    private function assertConfigHasExpectedPaths(ConfigSwitcher $switcher) : void
    {
        $this->assertConfigHasPath($switcher, '/path/to/application-framework');
        $this->assertConfigHasPath($switcher, '/path/to/application-utils-core');
        $this->assertConfigHasPath($switcher, '/path/to/application-utils');
    }

    private function assertConfigHasPath(ConfigSwitcher $switcher, string $path) : void
    {
        $config = $switcher->getMainFile()->getData();
        $this->assertArrayHasKey('repositories', $config);
        $this->assertIsArray($config['repositories']);

        foreach($config['repositories'] as $repository)
        {
            if(isset($repository['type']) && $repository['type'] === 'path' && $repository['url'] === $path) {
                $this->addToAssertionCount(1);
                return;
            }
        }

        $this->fail('No path repository found for path: ' . $path);
    }

    private function assertConfigHasCustomVersion(ConfigSwitcher $switcher, string $packageName, string $version) : void
    {
        $config = $switcher->getMainFile()->getData();
        $this->assertArrayHasKey('repositories', $config);
        $this->assertIsArray($config['repositories']);

        foreach($config['repositories'] as $repository)
        {
            if(
                isset($repository['type'], $repository['options']['versions'][$packageName])
                && $repository['type'] === 'path'
                && $repository['options']['versions'][$packageName] === $version
            ) {
                $this->addToAssertionCount(1);
                return;
            }
        }

        $this->fail('No version definition found for package name: ' . $packageName);
    }

    // endregion
}
