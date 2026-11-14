<?php

namespace App\Models\Organization;

use App\Helpers\FileApps;
use App\Models\Survey\SurveyQuestion;
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
 * (e.g. Environmental, External Affairs, Human Resources, Supply Chain).
 * Each department is split into sections (focus areas).
 */
#[Fillable(['code', 'name', 'description', 'icon', 'color', 'image_path', 'sort_order', 'is_active'])]
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
        ];
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
     * Section names as a phrase, e.g. "Sustainability & Impact".
     *
     * @param  Collection<int, DepartmentSection>|null  $sections  Defaults to the loaded active sections.
     */
    public function sectionsLabel(?Collection $sections = null): string
    {
        $names = ($sections ?? $this->sections->where('is_active', true))->pluck('name')->values();

        if ($names->count() <= 1) {
            return (string) $names->first();
        }

        return $names->slice(0, -1)->implode(', ').' & '.$names->last();
    }

    /**
     * Public URL of the department image, if any.
     */
    public function imageUrl(): ?string
    {
        return FileApps::url($this->image_path);
    }
}
