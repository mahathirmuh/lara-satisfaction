<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

/**
 * Primary keys generated as fully random (version 4) UUIDs.
 *
 * Laravel's HasUuids generates UUIDv7 and HasVersion4Uuids generates
 * timestamp-first "ordered" UUIDs. Both embed the creation time, which would
 * let anonymous survey answers be correlated with participation records.
 * This trait keeps identifiers free of any temporal information.
 */
trait HasRandomUuids
{
    use HasUuids;

    /**
     * Generate a new random UUID (version 4) for the model.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }
}
