<?php

namespace App\Models\Log;

use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail of state-changing web requests (payload redacted).
 */
#[Table('activity_logs')]
#[Fillable(['subject', 'url', 'method', 'route_name', 'status_code', 'data', 'agent', 'ip', 'user_id'])]
class LogActivity extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
