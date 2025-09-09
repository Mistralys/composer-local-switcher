<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

use Mistralys\ComposerSwitcher\ComposerSwitcherException;

class LockFile extends BaseFile
{
    /**
     * @var ConfigFile
     */
    private $configFile;

    public function __construct(ConfigFile $configFile)
    {
        $this->configFile = $configFile;

        parent::__construct(str_replace('.json', '.lock', $configFile->getPath()));
    }

    public function getContent() : string
    {
        $content = file_get_contents($this->getPath());

        if($content !== false) {
            return $content;
        }

        throw new ComposerSwitcherException(
            'Failed to read file: ' . $this->getPath(),
            ComposerSwitcherException::ERROR_CANNOT_READ_FILE
        );
    }

    public function getConfigFile(): ConfigFile
    {
        return $this->configFile;
    }
}
