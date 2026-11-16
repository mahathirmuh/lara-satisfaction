<?php

namespace App\Models\Survey;

use App\Enums\QuestionType;
use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use Database\Factories\Survey\SurveyQuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Question of a survey. A department question is asked about the section
 * the employee chooses to evaluate in that department ("{section}" in the
 * text is replaced by its name); a question assigned to a section is only
 * asked when that section is chosen; a general question (department null)
 * is asked once.
 */
#[Fillable(['survey_id', 'department_id', 'department_section_id', 'type', 'question', 'help_text', 'is_required', 'sort_order'])]
class SurveyQuestion extends Model
{
    /** @use HasFactory<SurveyQuestionFactory> */
    use HasFactory;

    /**
     * Placeholder replaced by the name of the evaluated section.
     */
    public const SECTION_PLACEHOLDER = '{section}';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'survey_id' => 'integer',
            'department_id' => 'integer',
            'department_section_id' => 'integer',
            'type' => QuestionType::class,
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Survey, $this>
     */
    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return BelongsTo<DepartmentSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(DepartmentSection::class, 'department_section_id');
    }

    /**
     * @return HasMany<SurveyAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(SurveyAnswer::class);
    }

    /**
     * Whether the question collects a 1-5 rating.
     */
    public function isRating(): bool
    {
        return $this->type->isRating();
    }

    /**
     * Whether the question is asked about the section the employee chooses
     * (a department question that is not tied to one section).
     */
    public function followsChosenSection(): bool
    {
        return $this->department_id !== null && $this->department_section_id === null;
    }

    /**
     * Whether the question applies when the given section is evaluated.
     */
    public function appliesTo(?int $sectionId): bool
    {
        return $this->department_section_id === null || $this->department_section_id === $sectionId;
    }

    /**
     * Question text in the interface language. The standard questions
     * (SurveySeeder::SECTION_QUESTIONS) are translated, their English text
     * being the key; a text typed by an admin has no translation and stays
     * as it is. "{section}" is kept.
     */
    public function text(): string
    {
        $translated = __($this->question);

        return is_string($translated) ? $translated : $this->question;
    }

    /**
     * Question text for the wizard: escaped, with every "{section}" wrapped in
     * a span the page fills with the name of the chosen section.
     */
    public function textHtml(string $sectionFallback): HtmlString
    {
        $parts = array_map(e(...), explode(self::SECTION_PLACEHOLDER, $this->text()));

        return new HtmlString(implode('<span class="js-section-name">'.e($sectionFallback).'</span>', $parts));
    }

    /**
     * Question text with "{section}" replaced by the given section name.
     */
    public function textFor(string $sectionName): string
    {
        return str_replace(self::SECTION_PLACEHOLDER, $sectionName, $this->text());
    }

    /**
     * Question text for reports and exports, where answers cover several
     * sections: "{section}" becomes "[section]".
     */
    public function displayText(): string
    {
        return $this->textFor(__('[section]'));
    }

    /**
     * Sort key of the question inside its survey: departments in display order
     * (general questions last), then the question order. Uses the loaded
     * department relation.
     *
     * @return array<int, int|string>
     */
    public function displayPosition(): array
    {
        return [
            $this->department === null ? 1 : 0,
            $this->department?->sort_order ?? 0,
            $this->department?->name ?? '',
            $this->sort_order,
            $this->id,
        ];
    }

    /**
     * "Department / Section", "Department" or "General" (uses the loaded relations).
     */
    public function contextLabel(): string
    {
        return collect([$this->department?->name ?? __('General'), $this->section?->name])
            ->filter()
            ->implode(' / ');
    }

    /**
     * The question as the audit trail records it, by the names of its form.
     *
     * @return array<string, mixed>
     */
    public function auditValues(): array
    {
        return [
            'Survey' => $this->survey?->title,
            'Department' => $this->department?->name,
            'Section' => $this->section?->name,
            'Type' => $this->type?->name,
            'Question' => $this->question,
            'Help text' => $this->help_text,
            'Required' => (bool) $this->is_required,
            'Order' => $this->sort_order,
        ];
    }

    /**
     * What the audit trail calls the question: its text, cut short.
     */
    public function auditLabel(): string
    {
        return Str::limit((string) $this->question, 120);
    }
}
