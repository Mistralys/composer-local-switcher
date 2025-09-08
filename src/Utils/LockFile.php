<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

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

    public function getConfigFile(): ConfigFile
    {
        return $this->configFile;
    }
}
