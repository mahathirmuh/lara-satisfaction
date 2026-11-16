<?php

namespace App\Models\Organization;

use App\Helpers\FileApps;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyQuestion;
use Database\Factories\Organization\DepartmentSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Section (service unit) of a department, e.g. External Affairs: Asset
 * Protection. Employees choose the section they evaluate per department and
 * see its overview first: team picture, work summary and role overview
 * (the description).
 */
#[Fillable([
    'department_id', 'name', 'description',
    'team_photo_path', 'team_caption', 'work_summary_path', 'work_summary_caption',
    'sort_order', 'is_active',
])]
class DepartmentSection extends Model
{
    /** @use HasFactory<DepartmentSectionFactory> */
    use HasFactory;

    /**
     * Folder of the section images on the public disk.
     */
    public const IMAGE_DIRECTORY = 'sections';

    /**
     * Uploadable images: form field => column.
     *
     * @var array<string, string>
     */
    public const IMAGES = [
        'team_photo' => 'team_photo_path',
        'work_summary' => 'work_summary_path',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<SurveyQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    /**
     * Anonymous answers that evaluated this section.
     *
     * @return HasMany<SurveyAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /**
     * Whether a survey asks about this section or has evaluated it. Such a section
     * keeps its department and is deactivated instead of deleted, so past results
     * stay attributed to it.
     */
    public function isUsedInSurveys(): bool
    {
        return $this->questions()->exists() || $this->answers()->exists();
    }

    /**
     * Only active sections.
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Display order.
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Public URL of the team picture, if any.
     */
    public function teamPhotoUrl(): ?string
    {
        return FileApps::url($this->team_photo_path);
    }

    /**
     * Public URL of the work summary image, if any.
     */
    public function workSummaryUrl(): ?string
    {
        return FileApps::url($this->work_summary_path);
    }

    /**
     * The section as the audit trail records it, by the names of its form.
     *
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'Department' => $this->department?->name,
            'Name' => $this->name,
            'Description' => $this->description,
            'Team picture' => $this->team_photo_path,
            'Team caption' => $this->team_caption,
            'Work summary' => $this->work_summary_path,
            'Work summary caption' => $this->work_summary_caption,
            'Order' => $this->sort_order,
            'Active' => (bool) $this->is_active,
        ];
    }
}
