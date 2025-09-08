<?php
/**
 * @package Composer Switcher
 * @subackage Core
 */

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher;

use Mistralys\ComposerSwitcher\Utils\ConfigFile;
use Mistralys\ComposerSwitcher\Utils\ConsoleWriter;

/**
 * @package Composer Switcher
 * @subackage Core
 */
class ConfigSwitcher
{
    public const MODE_DEV = 'dev';
    public const MODE_PROD = 'prod';

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

    public function __construct(ConfigFile $prodFile, ConfigFile $devConfig, ConfigFile $mainFile)
    {
        $this->prodFile = $prodFile;
        $this->devFile = $devConfig;
        $this->mainFile = $mainFile;
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
        ConsoleWriter::header('Switching to %s composer config', strtoupper($mode));

        $config = $this->prodFile->getData();

        if ($mode === self::MODE_DEV) {
            $config = $this->adjustForDev($config);
        }

        $this->mainFile->putData($config);
    }

    private function adjustForDev(array $config) : array
    {
        if(!$this->devFile->exists()) {
            throw new ComposerSwitcherException(
                'ERROR: The DEV composer config file does not exist.',
                ComposerSwitcherException::ERROR_DEV_FILE_MISSING
            );
        }

        $devConfig = $this->devFile->getData();

        ConsoleWriter::line1('Adjusting config for DEV...');

        if(!isset($devConfig['local-repositories']) || !is_array($devConfig['local-repositories'])) {
            throw new ComposerSwitcherException(
                'ERROR: The DEV composer config does not contain any local repositories.',
                ComposerSwitcherException::ERROR_INVALID_JSON_STRUCTURE
            );
        }

        foreach($devConfig['local-repositories'] as $repo)
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

            // Attempt to find an existing repository entry
            $found = false;
            foreach ($config['repositories'] as $i => $repository)
            {
                if (!isset($repository['url']) || stripos($repository['url'], $packageName) === false) {
                    continue;
                }

                $found = true;

                ConsoleWriter::line1('- UPDATE | [%s] | Overwriting existing repository entry.', $packageName);
                $config['repositories'][$i] = $repoEntry;

                break;
            }

            // The package was not found, add it.
            if(!$found) {
                $config['repositories'][] = $repoEntry;
                ConsoleWriter::line1('- ADD | [%s] | Adding new repository entry.', $packageName);
            }
        }

        ConsoleWriter::newline();

        return $config;
    }
}
