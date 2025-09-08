<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

use JsonException;
use Mistralys\ComposerSwitcher\ComposerSwitcherException;

class ConfigFile
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function exists() : bool
    {
        return file_exists($this->path);
    }

    /**
     * @return array<int|string, mixed>
     * @throws ComposerSwitcherException
     */
    public function getData() : array
    {
        $json = file_get_contents($this->path);
        if($json === false) {
            throw new ComposerSwitcherException('Failed to read file: ' . $this->path);
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ComposerSwitcherException(
                'Failed to decode JSON from file: ' . $this->path . '. Error: ' . $e->getMessage(),
                ComposerSwitcherException::ERROR_CANNOT_DECODE_JSON,
                $e
            );
        }

        if(!is_array($data)) {
            throw new ComposerSwitcherException(
                'Decoded JSON is not an array in file: ' . $this->path,
                ComposerSwitcherException::ERROR_INVALID_JSON_STRUCTURE
            );
        }

        return $data;
    }

    /**
     * Saves the specified data to the file as JSON.
     *
     * @param array<int|string,mixed> $data
     * @return void
     * @throws ComposerSwitcherException
     */
    public function putData(array $data) : void
    {
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $e) {
            throw new ComposerSwitcherException(
                'Failed to encode data to JSON for file: ' . $this->path . '. Error: ' . $e->getMessage(),
                ComposerSwitcherException::ERROR_CANNOT_ENCODE_JSON,
                $e
            );
        }

        if(!file_put_contents($this->path, $json . PHP_EOL)) {
            throw new ComposerSwitcherException(
                'Failed to write data to file: ' . $this->path,
                ComposerSwitcherException::ERROR_CANNOT_WRITE_FILE
            );
        }
    }
}
