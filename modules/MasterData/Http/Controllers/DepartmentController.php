<?php

namespace Modules\MasterData\Http\Controllers;

use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\MasterData\Http\Requests\DepartmentRequest;
use Modules\MasterData\Repositories\DepartmentInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Departments assessed by the satisfaction survey (with illustration).
 */
class DepartmentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected DepartmentInterface $departments) {}

    /**
     * Department page.
     */
    public function index(Request $request): View
    {
        return view('MasterData::department.index', [
            'colors' => Department::COLORS,
            'can' => $request->user()->menuAbilities('master-data.department'),
            'canManageSections' => $request->user()->canAccessMenu('master-data.section'),
        ]);
    }

    /**
     * Departments for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $params = Apps::tableParams($request, ['code', 'name', 'sort_order', 'is_active'], 'sort_order');

        $departments = $this->departments->paginate($params, [
            'is_active' => in_array($request->query('is_active'), ['0', '1'], true) ? $request->query('is_active') : null,
        ]);

        return Apps::tableResponse($departments, fn (Department $department): array => [
            'id' => $department->id,
            'code' => $department->code,
            'name' => $department->name,
            'icon' => $department->icon,
            'color' => $department->color,
            'image_url' => $department->imageUrl(),
            'sections' => $department->sections
                ->map(fn (DepartmentSection $section): array => ['name' => $section->name, 'is_active' => $section->is_active])
                ->values(),
            'sort_order' => $department->sort_order,
            'is_active' => $department->is_active,
            'questions_count' => $department->questions_count,
            'employees_count' => $department->employees_count,
        ]);
    }

    /**
     * A department (JSON for the edit modal).
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'data' => [
                ...$department->only(['id', 'code', 'name', 'description', 'icon', 'color', 'sort_order', 'is_active']),
                'manager_job_titles' => implode("\n", $department->manager_job_titles ?? []),
                'directory_names' => implode("\n", $department->directory_names ?? []),
                'image_url' => $department->imageUrl(),
            ],
        ]);
    }

    /**
     * Create a department.
     */
    public function store(DepartmentRequest $request): JsonResponse
    {
        $department = $this->departments->create($request->departmentAttributes(), $request->file('image'));

        return response()->json([
            'message' => __('Department created successfully.'),
            'data' => ['id' => $department->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a department.
     */
    public function update(DepartmentRequest $request, Department $department): JsonResponse
    {
        $this->departments->update(
            $department,
            $request->departmentAttributes(),
            $request->file('image'),
            $request->boolean('remove_image'),
        );

        return response()->json(['message' => __('Department updated successfully.')]);
    }

    /**
     * Delete a department that is not used by any question.
     */
    public function destroy(Department $department): JsonResponse
    {
        if ($department->questions()->exists()) {
            return response()->json([
                'message' => __('This department is used by survey questions. Deactivate it instead of deleting it.'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->departments->delete($department);

        return response()->json(['message' => __('Department deleted successfully.')]);
    }
}
