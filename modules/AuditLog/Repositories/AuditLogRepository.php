<?php

namespace Modules\AuditLog\Repositories;

use App\Helpers\Apps;
use App\Models\Log\AuditLog;
use App\Models\UserManagement\User;
use App\Services\Audit\AuditLogger;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogRepository implements AuditLogInterface
{
    /**
     * {@inheritDoc}
     */
    public function entries(string $search, string $action, string $entityType, ?CarbonInterface $from, ?CarbonInterface $to, int $perPage): LengthAwarePaginator
    {
        return AuditLog::query()
            ->with('user:id,name')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $pattern = Apps::likePattern($search);

                $query->where(function (Builder $where) use ($pattern): void {
                    $where->where('user_email', 'like', $pattern)
                        ->orWhereIn('user_id', User::withTrashed()->where('name', 'like', $pattern)->select('id'))
                        // A failed sign-in has no account: what was typed into the form is in its values.
                        ->orWhere(fn (Builder $failed) => $failed->where('action', AuditLogger::LOGIN_FAILED)->where('new_values', 'like', $pattern));
                });
            })
            ->when($action !== '', fn (Builder $query) => $query->where('action', $action))
            ->when($entityType !== '', fn (Builder $query) => $query->where('entity_type', $entityType))
            // Whole days: "from" its first moment, "to" up to the next day.
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn (Builder $query) => $query->where('created_at', '<', $to->copy()->addDay()->startOfDay()))
            ->orderByDesc('created_at')
            // Entries written by one action share their second: without this, rows would repeat across pages, or never show.
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function actions(): array
    {
        return AuditLog::query()->distinct()->pluck('action')->all();
    }

    /**
     * {@inheritDoc}
     */
    public function entityTypes(): array
    {
        return AuditLog::query()->whereNotNull('entity_type')->distinct()->pluck('entity_type')->all();
    }
}
