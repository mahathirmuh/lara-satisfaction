<?php

namespace Modules\SystemLog\Repositories;

use App\Models\Log\LogActivity;
use App\Models\Log\LogError;
use Illuminate\Pagination\LengthAwarePaginator;

interface SystemLogInterface
{
    /**
     * Paginated activity logs, newest first by default.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{method?: string|null}  $filters
     * @return LengthAwarePaginator<int, LogActivity>
     */
    public function activityLogs(array $params, array $filters = []): LengthAwarePaginator;

    /**
     * Paginated error logs, newest first by default.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @return LengthAwarePaginator<int, LogError>
     */
    public function errorLogs(array $params): LengthAwarePaginator;
}
