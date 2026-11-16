<?php

namespace Modules\MasterData\Repositories;

use App\Helpers\Apps;
use App\Helpers\FileApps;
use App\Models\Organization\DepartmentSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SectionRepository implements SectionInterface
{
    /**
     * Sortable columns mapped to their qualified names.
     *
     * @var array<string, string>
     */
    protected const SORT_COLUMNS = [
        'department' => 'departments.sort_order',
        'name' => 'department_sections.name',
        'sort_order' => 'department_sections.sort_order',
        'is_active' => 'department_sections.is_active',
    ];

    /**
     * {@inheritDoc}
     *
     * The tie-breakers skip the column already sorted on: SQL Server rejects an
     * ORDER BY list that names a column twice.
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator
    {
        $departmentId = $filters['department_id'] ?? null;
        $isActive = $filters['is_active'] ?? null;
        $sortColumn = self::SORT_COLUMNS[$params['order_by']] ?? self::SORT_COLUMNS['department'];

        return DepartmentSection::query()
            ->select('department_sections.*')
            ->join('departments', 'departments.id', '=', 'department_sections.department_id')
            ->with('department:id,name,color,icon')
            ->withCount('questions')
            ->withExists('answers')
            ->when(is_numeric($departmentId), fn (Builder $query) => $query->where('department_sections.department_id', (int) $departmentId))
            ->when($isActive !== null && $isActive !== '', fn (Builder $query) => $query->where('department_sections.is_active', (bool) $isActive))
            ->when($params['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('department_sections.name', 'like', Apps::likePattern($search))
                    ->orWhere('departments.name', 'like', Apps::likePattern($search))
            ))
            ->orderBy($sortColumn, $params['order_dir'])
            ->when($sortColumn !== 'departments.sort_order', fn (Builder $query) => $query->orderBy('departments.sort_order'))
            ->when($sortColumn !== 'department_sections.sort_order', fn (Builder $query) => $query->orderBy('department_sections.sort_order'))
            ->orderBy('department_sections.id')
            ->paginate($params['per_page']);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, array $uploads = []): DepartmentSection
    {
        $data['sort_order'] ??= (int) DepartmentSection::query()->where('department_id', $data['department_id'])->max('sort_order') + 1;

        foreach (DepartmentSection::IMAGES as $field => $column) {
            $data[$column] = isset($uploads[$field])
                ? FileApps::storeImage($uploads[$field], DepartmentSection::IMAGE_DIRECTORY, $field)
                : null;
        }

        return DepartmentSection::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(DepartmentSection $section, array $data, array $uploads = [], array $removals = []): DepartmentSection
    {
        $data['sort_order'] ??= $section->sort_order;

        foreach (DepartmentSection::IMAGES as $field => $column) {
            $data[$column] = FileApps::replaceImage(
                $section->{$column},
                $uploads[$field] ?? null,
                in_array($field, $removals, true),
                DepartmentSection::IMAGE_DIRECTORY,
                $field,
            );
        }

        $section->update($data);

        return $section;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(DepartmentSection $section): void
    {
        $imagePaths = array_map(fn (string $column): ?string => $section->{$column}, DepartmentSection::IMAGES);

        $section->delete();

        foreach ($imagePaths as $imagePath) {
            FileApps::delete($imagePath);
        }
    }
}
