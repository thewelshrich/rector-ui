<?php

namespace RectorUi;

final class BrowserLauncher
{
    public function open(string $osFamily, string $url): void
    {
        $command = $this->buildCommand($osFamily, $url);

        if ($command === null) {
            return;
        }

        @exec($command);
    }

    public function buildCommand(string $osFamily, string $url): ?string
    {
        $escapedUrl = escapeshellarg($url);

        if ($osFamily === 'Darwin') {
            return 'open ' . $escapedUrl;
        }

        if ($osFamily === 'Linux') {
            return 'xdg-open ' . $escapedUrl . ' > /dev/null 2>&1 &';
        }

        if ($osFamily === 'Windows') {
            return 'start "" ' . $escapedUrl;
        }

        return null;
    }
}
