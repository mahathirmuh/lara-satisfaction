<?php

namespace Modules\MasterData\Http\Controllers;

use App\Helpers\Apps;
use App\Http\Controllers\Controller;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\MasterData\Http\Requests\SectionRequest;
use Modules\MasterData\Repositories\SectionInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sections (focus areas) of the assessed departments,
 * e.g. Environmental: Waste Management.
 */
class SectionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(protected SectionInterface $sections) {}

    /**
     * Section page.
     */
    public function index(Request $request): View
    {
        return view('MasterData::section.index', [
            'departments' => Department::query()->ordered()->get(['id', 'name', 'is_active']),
            'selectedDepartmentId' => $request->integer('department_id') ?: null,
            'can' => $request->user()->menuAbilities('master-data.section'),
        ]);
    }

    /**
     * Sections for the table (JSON).
     */
    public function list(Request $request): JsonResponse
    {
        $params = Apps::tableParams($request, ['department', 'name', 'sort_order', 'is_active'], 'department');

        $sections = $this->sections->paginate($params, [
            'department_id' => $request->query('department_id'),
            'is_active' => in_array($request->query('is_active'), ['0', '1'], true) ? $request->query('is_active') : null,
        ]);

        return Apps::tableResponse($sections, fn (DepartmentSection $section): array => [
            'id' => $section->id,
            'department' => $section->department?->name,
            'department_color' => $section->department?->color ?? 'gray',
            'department_icon' => $section->department?->icon,
            'name' => $section->name,
            'description' => $section->description,
            'team_photo_url' => $section->teamPhotoUrl(),
            'work_summary_url' => $section->workSummaryUrl(),
            'sort_order' => $section->sort_order,
            'is_active' => $section->is_active,
            'questions_count' => $section->questions_count,
        ]);
    }

    /**
     * A section (JSON for the edit modal).
     */
    public function show(DepartmentSection $section): JsonResponse
    {
        return response()->json([
            'data' => [
                ...$section->only([
                    'id', 'department_id', 'name', 'description', 'team_caption', 'work_summary_caption', 'sort_order', 'is_active',
                ]),
                'team_photo_url' => $section->teamPhotoUrl(),
                'work_summary_url' => $section->workSummaryUrl(),
            ],
        ]);
    }

    /**
     * Create a section.
     */
    public function store(SectionRequest $request): JsonResponse
    {
        $section = $this->sections->create($request->sectionAttributes(), $request->imageUploads());

        return response()->json([
            'message' => 'Section created successfully.',
            'data' => ['id' => $section->id],
        ], Response::HTTP_CREATED);
    }

    /**
     * Update a section.
     */
    public function update(SectionRequest $request, DepartmentSection $section): JsonResponse
    {
        $this->sections->update($section, $request->sectionAttributes(), $request->imageUploads(), $request->imageRemovals());

        return response()->json(['message' => 'Section updated successfully.']);
    }

    /**
     * Delete a section that is not used by any question.
     */
    public function destroy(DepartmentSection $section): JsonResponse
    {
        if ($section->questions()->exists()) {
            return response()->json([
                'message' => 'This section is used by survey questions. Deactivate it instead of deleting it.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->sections->delete($section);

        return response()->json(['message' => 'Section deleted successfully.']);
    }
}
