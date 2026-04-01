<?php

declare(strict_types=1);

namespace RectorUi\Application;

/**
 * Simple PSR-11 compatible service container.
 * Implementation will be built in Phase 1.
 */
final class Container
{
    /** @var array<string, object> */
    private array $services = [];

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }

    public function get(string $id): object
    {
        if (!$this->has($id)) {
            throw new \InvalidArgumentException(sprintf('Service "%s" not found in container.', $id));
        }

        return $this->services[$id];
    }

    public function set(string $id, object $service): void
    {
        $this->services[$id] = $service;
    }
}
