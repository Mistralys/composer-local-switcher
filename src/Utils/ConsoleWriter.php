<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

class ConsoleWriter
{
    /**
     * @var bool
     */
    private $enabled = false;

    /**
     * @param string $header
     * @param mixed ...$placeholders
     * @return void
     */
    public function header(string $header, ...$placeholders) : void
    {
        if($this->enabled === false) {
            return;
        }

        $this->separator();
        echo vsprintf($header, $placeholders).PHP_EOL;
        $this->separator();
        $this->newline();
    }

    /**
     * Level 1 line with indentation.
     *
     * @param string $line
     * @param mixed ...$placeholders
     * @return void
     */
    public function line1(string $line, ...$placeholders) : void
    {
        if($this->enabled === false) {
            return;
        }

        echo vsprintf('- '.$line, $placeholders).PHP_EOL;
    }

    /**
     * Level 2 line with indentation
     *
     * @param string $line
     * @param mixed ...$placeholders
     * @return void
     */
    public function line2(string $line, ...$placeholders) : void
    {
        if($this->enabled === false) {
            return;
        }

        echo vsprintf('  - '.$line, $placeholders).PHP_EOL;
    }

    /**
     * Level 3 line with indentation
     *
     * @param string $line
     * @param mixed ...$placeholders
     * @return void
     */
    public function line3(string $line, ...$placeholders) : void
    {
        if($this->enabled === false) {
            return;
        }

        echo vsprintf('    . '.$line, $placeholders).PHP_EOL;
    }

    public function newline() : void
    {
        if($this->enabled === false) {
            return;
        }

        echo PHP_EOL;
    }

    public function separator() : void
    {
        if($this->enabled === false) {
            return;
        }

        echo str_repeat('-', 65).PHP_EOL;
    }

    public function setEnabled(bool $enabled) : void
    {
        $this->enabled = $enabled;
    }
}