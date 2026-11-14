<?php

namespace Modules\SystemLog\Repositories;

use App\Helpers\Apps;
use App\Models\Log\LogActivity;
use App\Models\Log\LogError;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SystemLogRepository implements SystemLogInterface
{
    /**
     * {@inheritDoc}
     */
    public function activityLogs(array $params, array $filters = []): LengthAwarePaginator
    {
        return LogActivity::query()
            ->with('user:id,name,email')
            ->when($filters['method'] ?? null, fn (Builder $query, string $method) => $query->where('method', $method))
            ->when($params['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('route_name', 'like', Apps::likePattern($search))
                    ->orWhere('url', 'like', Apps::likePattern($search))
                    ->orWhere('ip', 'like', Apps::likePattern($search))
            ))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderByDesc('id')
            ->paginate($params['per_page']);
    }

    /**
     * {@inheritDoc}
     */
    public function errorLogs(array $params): LengthAwarePaginator
    {
        return LogError::query()
            ->when($params['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('message', 'like', Apps::likePattern($search))
                    ->orWhere('exception', 'like', Apps::likePattern($search))
                    ->orWhere('url', 'like', Apps::likePattern($search))
            ))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderByDesc('id')
            ->paginate($params['per_page']);
    }
}
