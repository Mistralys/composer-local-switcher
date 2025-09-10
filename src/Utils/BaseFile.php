<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

use DateTime;
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

    public function getBaseName() : string
    {
        return basename($this->path);
    }

    public function exists() : bool
    {
        return file_exists($this->path);
    }

    public function getModifiedDate() : ?DateTime
    {
        if(!$this->exists()) {
            return null;
        }

        return DateTime::createFromFormat('U', (string)filemtime($this->path));
    }

    public function requireModifiedDate() : DateTime
    {
        $date = $this->getModifiedDate();

        if($date !== null) {
            return $date;
        }

        throw new ComposerSwitcherException(
            sprintf(
                'Cannot get modified date, file %s does not exist.',
                $this->path
            ),
            ComposerSwitcherException::ERROR_CANNOT_GET_MODIFIED_DATE
        );
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
        if(!copy($this->getPath(), $target->getPath())) {
            throw new ComposerSwitcherException(
                'Failed to copy file from ' . $this->getPath() . ' to ' . $target->getPath(),
                ComposerSwitcherException::ERROR_CANNOT_COPY_FILE
            );
        }
    }

    /**
     * Like {@see copyTo()}, but only if both source and target files exist.
     *
     * @param BaseFile $target
     * @return void
     */
    public function tryCopyTo(BaseFile $target) : void
    {
        if($this->exists() && $target->exists()) {
            $this->copyTo($target);
        }
    }
}
