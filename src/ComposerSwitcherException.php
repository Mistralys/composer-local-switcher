<?php

declare(strict_types=1);

namespace Mistralys\ComposerSwitcher;

use Exception;

class ComposerSwitcherException extends Exception
{
    public const ERROR_DEV_FILE_MISSING = 182101;
    public const ERROR_CANNOT_DECODE_JSON = 182102;
    public const ERROR_INVALID_JSON_STRUCTURE = 182103;
    public const ERROR_CANNOT_ENCODE_JSON = 182104;
    public const ERROR_CANNOT_WRITE_FILE = 182105;
    public const ERROR_CANNOT_DELETE_FILE = 182106;
    public const ERROR_CANNOT_COPY_FILE = 182107;
    public const ERROR_CANNOT_READ_FILE = 182108;
    public const ERROR_CANNOT_GET_MODIFIED_DATE = 182109;
    public const ERROR_INVALID_SWITCH_MODE = 182110;
}
