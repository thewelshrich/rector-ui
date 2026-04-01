<?php

declare(strict_types=1);

namespace RectorUi\Exception;

class RectorProcessException extends \RuntimeException
{
    public static function fromCommand(string $command, string $error): self
    {
        return new self(
            sprintf('Rector process failed for command "%s": %s', $command, $error),
            0,
        );
    }
}
