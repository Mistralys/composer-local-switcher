<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

use Mistralys\ComposerSwitcher\ComposerSwitcherException;
use Mistralys\ComposerSwitcher\ConfigSwitcher;

class FlagFile extends BaseFile
{
    /**
     * @var string
     */
    private $mode;

    public function __construct(ConfigSwitcher $switcher, string $mode)
    {
        $this->mode = strtoupper($mode);

        parent::__construct(str_replace('.json', '.json.'.$this->mode, $switcher->getMainFile()->getPath()));
    }

    public function create() : self
    {
        if(file_put_contents($this->getPath(), $this->mode) !== false) {
            return $this;
        }

        throw new ComposerSwitcherException(
            sprintf(
                'Failed to write data to flag file %s.',
                $this->getBaseName()
            ),
            ComposerSwitcherException::ERROR_CANNOT_WRITE_FILE
        );
    }
}
