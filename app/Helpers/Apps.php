<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Shared helpers for the Merdeka table pages (JSON list endpoints consumed by
 * renderTableWithFeatures() in main/components/js).
 */
class Apps
{
    /**
     * Page sizes accepted by the table pagination.
     */
    public const PAGE_SIZES = [10, 25, 50, 100];

    /**
     * Read the table control parameters of a list request.
     *
     * Sort columns are matched against an allow-list because column names
     * cannot be protected by query bindings.
     *
     * @param  array<int, string>  $sortable
     * @return array{per_page: int, search: string|null, order_by: string, order_dir: string}
     */
    public static function tableParams(Request $request, array $sortable, string $defaultSort, string $defaultDir = 'asc'): array
    {
        $perPage = $request->integer('paginated', 10);
        $orderBy = (string) $request->query('orderBy', $defaultSort);
        $orderDir = strtolower((string) $request->query('orderDir', $defaultDir));
        $search = trim((string) $request->query('search', ''));

        return [
            'per_page' => in_array($perPage, self::PAGE_SIZES, true) ? $perPage : 10,
            'search' => $search !== '' ? mb_substr($search, 0, 100) : null,
            'order_by' => in_array($orderBy, $sortable, true) ? $orderBy : $defaultSort,
            'order_dir' => in_array($orderDir, ['asc', 'desc'], true) ? $orderDir : $defaultDir,
        ];
    }

    /**
     * Paginated JSON response in the Merdeka table format: {data, links, meta}.
     *
     * @param  callable(mixed): array<string, mixed>  $transform
     */
    public static function tableResponse(LengthAwarePaginator $paginator, callable $transform): JsonResponse
    {
        return response()->json([
            'data' => $paginator->getCollection()->map($transform)->values(),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * "Contains" pattern for a LIKE search. The value is always passed as a
     * query binding; wildcard characters typed by the user only broaden the
     * match (escaping them is not portable across SQLite, MySQL and PostgreSQL).
     */
    public static function likePattern(string $value): string
    {
        return '%'.$value.'%';
    }
}
