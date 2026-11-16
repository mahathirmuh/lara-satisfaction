<?php

namespace App\Models\Organization;

use App\Helpers\FileApps;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\User;
use Database\Factories\Organization\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Department whose services are assessed by the satisfaction survey
 * (the support service departments, e.g. Supply Chain, Finance, Interface).
 * Each department is split into sections (focus areas). Managers whose job
 * title matches manager_job_titles see the department's survey results. The
 * employees whose directory department is the department name or one of its
 * directory_names work here and are the survey respondents.
 */
#[Fillable(['code', 'name', 'description', 'icon', 'color', 'image_path', 'manager_job_titles', 'directory_names', 'sort_order', 'is_active'])]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    /**
     * Colours available in the Merdeka palette (custom.css).
     */
    public const COLORS = ['blue', 'green', 'purple', 'orange', 'red', 'yellow', 'pink', 'lime', 'brand', 'gray'];

    /**
     * Folder of the department images on the public disk.
     */
    public const IMAGE_DIRECTORY = 'departments';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'manager_job_titles' => 'array',
            'directory_names' => 'array',
        ];
    }

    /**
     * Active department ids by normalised directory department name (the
     * department name and its directory_names), to place employees.
     *
     * @return array<string, int>
     */
    public static function directoryNameMap(): array
    {
        $map = [];

        foreach (static::query()->active()->ordered()->get(['id', 'name', 'directory_names']) as $department) {
            foreach ([$department->name, ...($department->directory_names ?? [])] as $name) {
                $map[static::normalizeDirectoryName($name)] ??= (int) $department->id;
            }
        }

        return $map;
    }

    /**
     * Comparable form of a directory department name: lower case, "&" as
     * "and", single spaces ("Occupational Health & Safety" equals
     * "occupational health and safety").
     */
    public static function normalizeDirectoryName(string $name): string
    {
        return (string) preg_replace('/\s+/', ' ', trim(str_replace('&', ' and ', mb_strtolower($name))));
    }

    /**
     * @return HasMany<DepartmentSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(DepartmentSection::class)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return HasMany<SurveyQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class);
    }

    /**
     * The people who work in the department (imported from the directory):
     * the survey respondents.
     *
     * @return HasMany<User, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Only active departments.
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
     * Section names as a comma-separated list, e.g. "Sales Support, Cost Control, Finance"
     * (no "&" before the last name, because section names often contain one).
     *
     * @param  Collection<int, DepartmentSection>|null  $sections  Defaults to the loaded active sections.
     */
    public function sectionsLabel(?Collection $sections = null): string
    {
        return ($sections ?? $this->sections->where('is_active', true))->pluck('name')->implode(', ');
    }

    /**
     * Public URL of the department image, if any.
     */
    public function imageUrl(): ?string
    {
        return FileApps::url($this->image_path);
    }
}
