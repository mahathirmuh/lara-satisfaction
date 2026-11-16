<?php

namespace App\Models\Log;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Internal error monitoring: every reported exception.
 */
#[Table('error_logs')]
#[Fillable(['subject', 'exception', 'message', 'stack_trace', 'file', 'line', 'url', 'method', 'data', 'user_id'])]
class LogError extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }
}
