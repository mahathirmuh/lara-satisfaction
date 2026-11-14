<?php

namespace App\Models\Survey;

use App\Enums\SurveyStatus;
use App\Models\UserManagement\User;
use Database\Factories\Survey\SurveyDraftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Unfinished answers an employee saved to continue later ("Save as Draft").
 * The payload is encrypted and the draft is removed on submission, so the
 * submitted answers stay anonymous.
 */
#[Fillable(['survey_id', 'user_id', 'payload'])]
class SurveyDraft extends Model
{
    /** @use HasFactory<SurveyDraftFactory> */
    use HasFactory;

    use Prunable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'survey_id' => 'integer',
            'user_id' => 'integer',
            'payload' => 'encrypted:array',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Drafts of surveys that can no longer be submitted ("php artisan model:prune").
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->whereHas('survey', fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->where('status', '!=', SurveyStatus::Active)
            ->orWhereDate('end_date', '<', today()->toDateString())));
    }

    /**
     * Form values keyed by input name (e.g. "evaluations[5][12]" => "4"),
     * as the wizard restores them.
     *
     * @return array<string, string>
     */
    public function formValues(): array
    {
        $values = [];

        foreach ($this->payload['answers'] ?? [] as $questionId => $value) {
            $values["answers[{$questionId}]"] = (string) $value;
        }

        foreach ($this->payload['evaluations'] ?? [] as $sectionId => $answers) {
            foreach ((array) $answers as $questionId => $value) {
                $values["evaluations[{$sectionId}][{$questionId}]"] = (string) $value;
            }
        }

        return array_filter($values, fn (string $value): bool => $value !== '');
    }
}
