<?php

namespace RectorUi;

final class ApplicationVersion
{
    public static function get(): string
    {
        $composerFile = dirname(__DIR__) . '/composer.json';

        if (! file_exists($composerFile)) {
            return '0.1.0-dev';
        }

        $decoded = json_decode((string) file_get_contents($composerFile), true);

        if (! is_array($decoded) || ! isset($decoded['version'])) {
            return '0.1.0-dev';
        }

        return (string) $decoded['version'];
    }
}
