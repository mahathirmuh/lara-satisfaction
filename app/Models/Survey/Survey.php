<?php

namespace App\Models\Survey;

use App\Enums\SurveyStatus;
use App\Models\Organization\Department;
use App\Models\UserManagement\User;
use Database\Factories\Survey\SurveyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A survey period (e.g. "Operational Audit 2026").
 */
#[Fillable(['title', 'description', 'start_date', 'end_date', 'status', 'estimated_minutes', 'created_by'])]
class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => SurveyStatus::class,
            'estimated_minutes' => 'integer',
            'created_by' => 'integer',
        ];
    }

    /**
     * @return HasMany<SurveyQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<SurveyParticipation, $this>
     */
    public function participations(): HasMany
    {
        return $this->hasMany(SurveyParticipation::class);
    }

    /**
     * @return HasMany<SurveyResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    /**
     * @return HasMany<SurveyDraft, $this>
     */
    public function drafts(): HasMany
    {
        return $this->hasMany(SurveyDraft::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * Surveys accepting responses today.
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $today = today()->toDateString();

        $query->where('status', SurveyStatus::Active)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    /**
     * Whether the survey accepts responses today.
     */
    public function isOpen(): bool
    {
        return $this->status === SurveyStatus::Active
            && today()->betweenIncluded($this->start_date, $this->end_date);
    }

    /**
     * Days until the end date (0 = closes today, negative = already ended).
     */
    public function daysLeft(): int
    {
        return (int) round(today()->diffInDays($this->end_date->copy()->startOfDay(), false));
    }

    /**
     * Whether the survey is open and inside the reminder period before its
     * end date (from the first reminder day, by default H-14).
     */
    public function isClosingSoon(): bool
    {
        $reminderDays = config('survey.reminder_days', []);

        return $this->isOpen() && $reminderDays !== [] && $this->daysLeft() <= max($reminderDays);
    }

    /**
     * "Closes today", "Closes tomorrow" or "Closes in 5 days", in the
     * interface language.
     */
    public function deadlineLabel(): string
    {
        $daysLeft = $this->daysLeft();

        return match (true) {
            $daysLeft <= 0 => __('Closes today'),
            $daysLeft === 1 => __('Closes tomorrow'),
            default => __('Closes in :days days', ['days' => $daysLeft]),
        };
    }

    /**
     * "today", "tomorrow" or "in 5 days" for a number of days left.
     */
    public static function deadlineText(int $daysLeft): string
    {
        return match (true) {
            $daysLeft <= 0 => 'today',
            $daysLeft === 1 => 'tomorrow',
            default => "in {$daysLeft} days",
        };
    }

    /**
     * Whether answers were already submitted, which freezes the question set.
     */
    public function hasResponses(): bool
    {
        return $this->responses()->exists();
    }

    /**
     * Questions in the order employees answer them, used by the wizard, the
     * report and the export (see SurveyQuestion::displayPosition()).
     *
     * @param  array<int, string>  $with  Additional relations to eager load.
     * @return Collection<int, SurveyQuestion>
     */
    public function orderedQuestions(array $with = []): Collection
    {
        return $this->questions()
            ->with(['department', 'section', ...$with])
            ->get()
            ->sortBy(fn (SurveyQuestion $question): array => $question->displayPosition())
            ->values();
    }

    /**
     * Departments assessed by this survey, in display order. The question
     * subquery drops the relation's ordering: SQL Server rejects ORDER BY in a
     * subquery.
     *
     * @return Collection<int, Department>
     */
    public function departments(): Collection
    {
        return Department::query()
            ->whereIn('id', $this->questions()->getQuery()->reorder()->whereNotNull('department_id')->select('department_id'))
            ->ordered()
            ->get();
    }

    /**
     * Human readable period in the interface language, e.g.
     * "01 Nov 2026 - 30 Nov 2026" ("2026年11月1日 - 2026年11月30日" in Chinese).
     */
    public function periodLabel(): string
    {
        return $this->start_date->translatedFormat(__('d M Y')).' - '.$this->end_date->translatedFormat(__('d M Y'));
    }
}
