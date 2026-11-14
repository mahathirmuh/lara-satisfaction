<?php

namespace Modules\MasterData\Repositories;

use App\Models\Organization\DepartmentSection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

interface SectionInterface
{
    /**
     * Paginated department sections with their question count.
     *
     * @param  array{per_page: int, search: string|null, order_by: string, order_dir: string}  $params
     * @param  array{department_id?: int|string|null, is_active?: string|null}  $filters
     * @return LengthAwarePaginator<int, DepartmentSection>
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator;

    /**
     * Create a section (appended after the department's sections when no order is given).
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $uploads  Images keyed by form field (see DepartmentSection::IMAGES).
     */
    public function create(array $data, array $uploads = []): DepartmentSection;

    /**
     * Update a section, replacing uploaded images and removing the requested ones.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile>  $uploads  Images keyed by form field (see DepartmentSection::IMAGES).
     * @param  array<int, string>  $removals  Form fields whose image is removed.
     */
    public function update(DepartmentSection $section, array $data, array $uploads = [], array $removals = []): DepartmentSection;

    /**
     * Delete a section that is not used by any question, with its images.
     */
    public function delete(DepartmentSection $section): void;
}
