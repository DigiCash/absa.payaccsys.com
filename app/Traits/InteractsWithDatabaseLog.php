<?php

namespace App\Traits;

use App\Support\DatabaseLogProxy;

/**
 * @property-read DatabaseLogProxy $logDb
 */
trait InteractsWithDatabaseLog
{
    // Removed the property declaration here to prevent collision errors.

    public function __get(string $key): ?DatabaseLogProxy
    {
        return match ($key) {
            // Dynamically check the controller class, falling back if not set
            'logDb' => new DatabaseLogProxy($this->loggerName ?? 'fingo-application'),
            default => throw new \Error("Undefined property: " . static::class . "::\${$key}"),
        };
    }
}
