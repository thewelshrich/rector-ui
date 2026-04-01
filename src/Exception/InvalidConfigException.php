<?php

declare(strict_types=1);

namespace RectorUi\Exception;

class InvalidConfigException extends \RuntimeException
{
    public static function forKey(string $key, string $reason): self
    {
        return new self(sprintf('Invalid configuration for "%s": %s', $key, $reason));
    }
}
