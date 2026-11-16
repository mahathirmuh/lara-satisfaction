<?php

namespace Modules\MasterData\Repositories;

use App\Helpers\Apps;
use App\Helpers\FileApps;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentRepository implements DepartmentInterface
{
    /**
     * {@inheritDoc}
     */
    public function paginate(array $params, array $filters = []): LengthAwarePaginator
    {
        $isActive = $filters['is_active'] ?? null;

        return Department::query()
            ->with('sections')
            ->withCount(['questions', 'employees' => fn (Builder $query) => $query->where('is_active', true)])
            ->when($params['search'], fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query
                    ->where('name', 'like', Apps::likePattern($search))
                    ->orWhere('code', 'like', Apps::likePattern($search))
                    ->orWhereHas('sections', fn (Builder $query) => $query->where('name', 'like', Apps::likePattern($search)))
            ))
            ->when($isActive !== null && $isActive !== '', fn (Builder $query) => $query->where('is_active', (bool) $isActive))
            ->orderBy($params['order_by'], $params['order_dir'])
            ->orderBy('id')
            ->paginate($params['per_page']);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data, ?UploadedFile $image = null): Department
    {
        $data['image_path'] = $image ? FileApps::storeImage($image, Department::IMAGE_DIRECTORY) : null;

        return Department::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function update(Department $department, array $data, ?UploadedFile $image = null, bool $removeImage = false): Department
    {
        $data['image_path'] = FileApps::replaceImage($department->image_path, $image, $removeImage, Department::IMAGE_DIRECTORY);

        $department->update($data);

        return $department;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Department $department): void
    {
        $imagePaths = $department->sections()
            ->get(array_values(DepartmentSection::IMAGES))
            ->flatMap(fn (DepartmentSection $section): array => array_values($section->only(DepartmentSection::IMAGES)))
            ->push($department->image_path);

        $department->delete();

        $imagePaths->each(fn (?string $imagePath) => FileApps::delete($imagePath));
    }
}
