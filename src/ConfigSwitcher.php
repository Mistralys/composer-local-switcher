<?php
/**
 * @package Composer Switcher
 * @subackage Core
 */

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher;

use Mistralys\ComposerSwitcher\Utils\ConfigFile;
use Mistralys\ComposerSwitcher\Utils\ConsoleWriter;
use Mistralys\ComposerSwitcher\Utils\StatusFile;

/**
 * @package Composer Switcher
 * @subackage Core
 */
class ConfigSwitcher
{
    public const MODE_DEV = 'dev';
    public const MODE_PROD = 'prod';

    public const MESSAGE_NO_LOCK_FILE_FOUND = 182201;
    public const MESSAGE_CREATE_NEW_LOCK_FILE = 182202;
    public const KEY_LOCAL_REPOSITORIES = 'local-repositories';
    public const KEY_REPOSITORIES = 'repositories';

    /**
     * @var ConfigFile
     */
    private $prodFile;

    /**
     * @var ConfigFile
     */
    private $devFile;

    /**
     * @var ConfigFile
     */
    private $mainFile;

    /**
     * @var StatusFile
     */
    private $statusFile;
    
    /**
     * @var ConsoleWriter
     */
    private $console;

    /**
     * @var bool
     */
    private $displayMessages = true;

    /**
     * @param ConfigFile $mainFile The main `composer.json` file.
     * @param ConfigFile $prodFile The production `composer-prod.json` file.
     * @param ConfigFile $devConfig The development configuration file, containing the list of local repositories.
     */
    public function __construct(ConfigFile $mainFile, ConfigFile $prodFile, ConfigFile $devConfig)
    {
        $this->prodFile = $prodFile;
        $this->devFile = $devConfig;
        $this->mainFile = $mainFile;
        $this->statusFile = new StatusFile(str_replace('.json', '.status', $devConfig->getPath()));
        $this->console = new ConsoleWriter();
    }

    /**
     * @param bool $write
     * @return $this
     */
    public function setWriteToConsole(bool $write) : self
    {
        $this->console->setEnabled($write);
        return $this;
    }

    public function getMainFile(): ConfigFile
    {
        return $this->mainFile;
    }

    public function getDevFile(): ConfigFile
    {
        return $this->devFile;
    }

    public function getProdFile(): ConfigFile
    {
        return $this->prodFile;
    }

    public function getStatus(): StatusFile
    {
        return $this->statusFile;
    }

    public function switchToDevelopment() : void
    {
        $this->switchTo(self::MODE_DEV);
    }

    public function switchToProduction() : void
    {
        $this->switchTo(self::MODE_PROD);
    }

    public function switchTo(string $mode) : void
    {
        $this->console->header('Switching to %s composer config', strtoupper($mode));

        if(!$this->mainFile->getLockFile()->exists())
        {
            $this->addMessage(
                'WARNING: No lock file found. Please run `composer update` after switching the config.',
                self::MESSAGE_NO_LOCK_FILE_FOUND
            );

            $this->autoDisplayMessages();
            return;
        }

        $this->switch_copyLockFiles($mode);

        $this->statusFile->saveState($mode, $this);

        $this->autoDisplayMessages();
    }

    private function switch_copyLockFiles(string $mode) : void
    {
        $this->console->line1('Copying files for %s mode...', $mode);

        $isDev = $this->getStatus()->isDEV();
        $isProd = $this->getStatus()->isPROD();
        $isInitial = !$isDev && !$isProd;

        // Initial switch: Initialize the production files if they do not exist yet.
        if($isInitial)
        {
            $this->switch_initProductionFiles();
        }

        if($mode === self::MODE_DEV)
        {
            if($isDev) {
                $this->switch_case_DEV_DEV();
            } else {
                $this->switch_case_PROD_DEV();
            }
        }
        else
        {
            if($isProd) {
                $this->switch_case_PROD_PROD();
            } else {
                $this->switch_case_DEV_PROD();
            }
        }
    }

    private function switch_case_DEV_DEV() : void
    {
        $this->console->line1('Already in DEV mode, refreshing config...');
        $this->addMessage('Using Composer DEV configuration.');

        $this->switch_adjustConfigForDev();
    }

    private function switch_case_PROD_PROD() : void
    {
        $this->console->line1('Ignoring switch, already in PROD mode.');
        $this->addMessage('Using Composer PROD configuration.');

        $mainModified = $this->mainFile->requireModifiedDate();
        $prodModified = $this->prodFile->requireModifiedDate();

        if($mainModified > $prodModified)
        {
            $this->addMessage('Backing up the modified `composer.json`.');
            $this->mainFile->copyTo($this->prodFile);
        }
        else if($mainModified < $prodModified)
        {
            $this->addMessage('Updating `composer.json` with changes.');
            $this->prodFile->copyTo($this->mainFile);
        }
    }

    private function switch_case_DEV_PROD() : void
    {
        $this->console->line1('Switching from DEV to PROD...');
        $this->addMessage('Using Composer PROD configuration.');

        $mainLockFile = $this->mainFile->getLockFile();

        // Back up the DEV lock file if present
        if($mainLockFile->exists()) {
            $mainLockFile->copyTo($this->devFile->getLockFile());
        }

        // Restore the PROD files
        $this->prodFile->copyTo($this->mainFile);
        $this->prodFile->getLockFile()->copyTo($this->mainFile->getLockFile());

        $this->addMessage('Run `composer install` to use the production dependencies.');
    }

    private function switch_case_PROD_DEV() : void
    {
        $this->console->line1('Switching from PROD to DEV...');
        $this->addMessage('Using Composer DEV configuration.');

        $prodLockFile = $this->mainFile->getLockFile();

        // Back up the PROD lock file if present
        if($prodLockFile->exists()) {
            $prodLockFile->copyTo($this->prodFile->getLockFile());
        }

        if($this->devFile->getLockFile()->exists())
        {
            $this->devFile->getLockFile()->copyTo($this->mainFile->getLockFile());

            $this->addMessage('Run `composer install` to use the development dependencies.');
        }
        else if(!$this->devFile->getLockFile()->exists())
        {
            // Force re-creation of the lock file
            $this->mainFile->getLockFile()->delete();

            $this->addMessage(
                'Run `composer update` to create a DEV lock file.',
                self::MESSAGE_CREATE_NEW_LOCK_FILE
            );
        }

        // Generate and copy the DEV files
        $this->switch_adjustConfigForDev();
    }

    /**
     * Called on the initial switch only, independent of the target mode.
     * Ensures that the production files exist by copying them from the main file.
     */
    private function switch_initProductionFiles() : void
    {
        if(!$this->prodFile->exists())
        {
            $this->console->line1('Creating production config...');

            $this->console->line2('%s -> %s', $this->mainFile->getName(), $this->prodFile->getName());
            $this->mainFile->copyTo($this->prodFile);
        }

        if($this->mainFile->getLockFile()->exists())
        {
            $this->console->line1('Creating production lock file...');

            $this->console->line2('%s -> %s', $this->mainFile->getLockFile()->getName(), $this->prodFile->getLockFile()->getName());
            $this->mainFile->getLockFile()->copyTo($this->prodFile->getLockFile());
        }
    }

    private function autoDisplayMessages() : void
    {
        if($this->displayMessages === true) {
            $this->displayMessages();
        }
    }

    /**
     * @return $this
     */
    public function displayMessages() : self
    {
        if(!empty($this->messages)) {
            echo PHP_EOL;
            foreach($this->messages as $message) {
                echo $message . PHP_EOL;
            }
            echo PHP_EOL;
        }

        return $this;
    }

    /**
     * @var string[]
     */
    private $messages = array();

    private function addMessage(string $message, ...$args) : void
    {
        $this->messages[] = sprintf($message, ...$args);
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    private function switch_adjustConfigForDev() : void
    {
        $config = $this->prodFile->getData();

        if(!$this->devFile->exists()) {
            throw new ComposerSwitcherException(
                'ERROR: The DEV composer config file does not exist.',
                ComposerSwitcherException::ERROR_DEV_FILE_MISSING
            );
        }

        $devConfig = $this->devFile->getData();

        $this->console->line1('Adjusting config for DEV...');

        if(!isset($devConfig[self::KEY_LOCAL_REPOSITORIES]) || !is_array($devConfig[self::KEY_LOCAL_REPOSITORIES])) {
            throw new ComposerSwitcherException(
                sprintf(
                    'ERROR: The DEV composer config does not contain the [%s] key, or it is not an array.',
                    self::KEY_LOCAL_REPOSITORIES
                ),
                ComposerSwitcherException::ERROR_INVALID_JSON_STRUCTURE
            );
        }

        foreach($devConfig[self::KEY_LOCAL_REPOSITORIES] as $repo)
        {
            if(!isset($repo['package-name'], $repo['path']) || !is_string($repo['package-name']) || !is_string($repo['path'])) {
                throw new ComposerSwitcherException(
                    'ERROR: Invalid local repository entry in DEV composer config.',
                    ComposerSwitcherException::ERROR_INVALID_JSON_STRUCTURE
                );
            }

            $packageName = $repo['package-name'];
            $path = $repo['path'];

            // Set the version requirement to any version.
            $config['require'][$packageName] = '*';

            $repoEntry = array(
                'type' => 'path',
                'url' => $path,
                'options' => array(
                    'symlink' => true
                ),
            );

            if(!isset($config[self::KEY_REPOSITORIES]) || !is_array($config[self::KEY_REPOSITORIES])) {
                $config[self::KEY_REPOSITORIES] = array();
            }

            // Attempt to find an existing repository entry
            $found = false;
            foreach ($config[self::KEY_REPOSITORIES] as $i => $repository)
            {
                if (!isset($repository['url'])) {
                    continue;
                }

                if(
                    stripos($repository['url'], $packageName) === false
                    &&
                    // GitHub repository URLs use hyphens instead of underscores.
                    // The package name may use either.
                    stripos($repository['url'], str_replace('_', '-', $packageName)) === false)
                {
                    continue;
                }

                $found = true;

                $this->console->line1('- UPDATE | [%s] | Overwriting existing repository entry.', $packageName);
                $config[self::KEY_REPOSITORIES][$i] = $repoEntry;

                break;
            }

            // The package was not found, add it.
            if(!$found) {
                $config[self::KEY_REPOSITORIES][] = $repoEntry;
                $this->console->line1('- ADD | [%s] | Adding new repository entry.', $packageName);
            }
        }

        $this->mainFile->putData($config);

        $this->console->newline();

        $this->addMessage('Rebuilt a fresh DEV `composer.json`.');
    }
}
