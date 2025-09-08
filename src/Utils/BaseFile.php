<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

use Mistralys\ComposerSwitcher\ComposerSwitcherException;

abstract class BaseFile
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function exists() : bool
    {
        return file_exists($this->path);
    }

    public function delete() : void
    {
        if(!$this->exists()) {
            return;
        }

        if(!unlink($this->path)) {
            throw new ComposerSwitcherException(
                'Failed to delete file: ' . $this->path,
                ComposerSwitcherException::ERROR_CANNOT_DELETE_FILE
            );
        }
    }

    public function getName() : string
    {
        return basename($this->path);
    }

    public function copyTo(BaseFile $target) : void
    {
        if($target->exists()) {
            $target->delete();
        }

        if(!copy($this->getPath(), $target->getPath())) {
            throw new ComposerSwitcherException(
                'Failed to copy file from ' . $this->getPath() . ' to ' . $target->getPath(),
                ComposerSwitcherException::ERROR_CANNOT_COPY_FILE
            );
        }
    }
}