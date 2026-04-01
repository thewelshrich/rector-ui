<?php

declare(strict_types=1);

namespace RectorUi\Exception;

class ProjectNotFoundException extends \RuntimeException
{
    public static function atPath(string $path): self
    {
        return new self(sprintf('No valid PHP project found at: %s', $path));
    }
}
