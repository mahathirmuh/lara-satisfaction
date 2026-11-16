<?php

namespace Modules\AuditLog\Repositories;

use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;

interface AuditLogInterface
{
    /**
     * The entries of the audit trail, newest first.
     *
     * @param  string  $search  the address the entry was written with, the name of that account now, or what was typed
     *                          into a failed sign-in; '' for all
     * @param  string  $action  one action key ("user.updated"), or '' for all
     * @param  string  $entityType  one kind of record ("Survey"), or '' for all
     * @param  CarbonInterface|null  $from  the first day, from its first moment
     * @param  CarbonInterface|null  $to  the last day, up to its end
     */
    public function entries(string $search, string $action, string $entityType, ?CarbonInterface $from, ?CarbonInterface $to, int $perPage): LengthAwarePaginator;

    /**
     * The actions recorded so far.
     *
     * @return array<int, string>
     */
    public function actions(): array;

    /**
     * The kinds of record the trail has entries about.
     *
     * @return array<int, string>
     */
    public function entityTypes(): array;
}
