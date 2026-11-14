<?php

namespace Modules\MasterData\Repositories;

use App\Models\Organization\Department;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

interface DepartmentInterface
{
    /**
     * Paginated departments with their sections and question count.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{is_active?: string|null}  $filters
     * @return LengthAwarePaginator<int, Department>
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator;

    /**
     * Create a department with an optional image.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $image = null): Department;

    /**
     * Update a department, replacing or removing its image.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Department $department, array $data, ?UploadedFile $image = null, bool $removeImage = false): Department;

    /**
     * Delete a department that is not used by any question (and its image).
     */
    public function delete(Department $department): void;
}
