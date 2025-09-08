<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher\Utils;

class ConsoleWriter
{
    /**
     * @param string $header
     * @param mixed ...$placeholders
     * @return void
     */
    public static function header(string $header, ...$placeholders) : void
    {
        self::separator();
        echo vsprintf($header, $placeholders).PHP_EOL;
        self::separator();
        self::newline();
    }

    /**
     * Level 1 line with indentation.
     *
     * @param string $line
     * @param mixed ...$placeholders
     * @return void
     */
    public static function line1(string $line, ...$placeholders) : void
    {
        echo vsprintf('- '.$line, $placeholders).PHP_EOL;
    }

    /**
     * Level 2 line with indentation
     *
     * @param string $line
     * @param mixed ...$placeholders
     * @return void
     */
    public static function line2(string $line, ...$placeholders) : void
    {
        echo vsprintf('  - '.$line, $placeholders).PHP_EOL;
    }

    /**
     * Level 3 line with indentation
     *
     * @param string $line
     * @param mixed ...$placeholders
     * @return void
     */
    public static function line3(string $line, ...$placeholders) : void
    {
        echo vsprintf('    . '.$line, $placeholders).PHP_EOL;
    }

    public static function newline() : void
    {
        echo PHP_EOL;
    }

    public static function separator() : void
    {
        echo str_repeat('-', 65).PHP_EOL;
    }
}