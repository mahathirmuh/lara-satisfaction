<?php

namespace Modules\SurveyForm\Repositories;

use App\Enums\ParticipationStatus;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyQuestion;
use App\Models\Survey\SurveyStart;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class SurveyFormRepository implements SurveyFormInterface
{
    /**
     * {@inheritDoc}
     */
    public function availableFor(User $user): Collection
    {
        return Survey::query()
            ->open()
            ->withCount('questions')
            ->withExists(['participations as has_participated' => fn ($query) => $query->whereBelongsTo($user)])
            ->withExists(['drafts as has_draft' => fn ($query) => $query->whereBelongsTo($user)])
            ->withExists(['starts as has_started' => fn ($query) => $query->whereBelongsTo($user)])
            ->orderBy('end_date')
            ->orderBy('id')
            ->get()
            ->each(fn (Survey $survey) => $survey->setAttribute('participation_status', ParticipationStatus::resolve(
                (bool) $survey->has_participated,
                (bool) $survey->has_draft,
                (bool) $survey->has_started,
            )));
    }

    /**
     * {@inheritDoc}
     */
    public function recordStart(Survey $survey, User $user): void
    {
        SurveyStart::query()->createOrFirst(
            ['survey_id' => $survey->id, 'user_id' => $user->id],
            ['started_at' => now()],
        );
    }

    /**
     * {@inheritDoc}
     */
    public function participationHistory(User $user, int $limit = 10): Collection
    {
        return SurveyParticipation::query()
            ->whereBelongsTo($user)
            ->with('survey:id,title,start_date,end_date,status')
            ->orderByDesc('submitted_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function hasParticipated(Survey $survey, User $user): bool
    {
        return SurveyParticipation::query()
            ->whereBelongsTo($survey)
            ->whereBelongsTo($user)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function draftFor(Survey $survey, User $user): ?SurveyDraft
    {
        return SurveyDraft::query()->whereBelongsTo($survey)->whereBelongsTo($user)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function saveDraft(Survey $survey, User $user, array $payload): SurveyDraft
    {
        $draft = SurveyDraft::query()->firstOrNew([
            'survey_id' => $survey->getKey(),
            'user_id' => $user->getKey(),
        ]);

        $draft->payload = $payload;
        $draft->touch(); // saves and refreshes "saved at" even when nothing changed

        return $draft;
    }

    /**
     * {@inheritDoc}
     */
    public function stepsFor(Survey $survey): SupportCollection
    {
        $questions = $survey->orderedQuestions();

        $sectionsByDepartment = DepartmentSection::query()
            ->whereIn('department_id', $questions->pluck('department_id')->filter()->unique())
            ->active()
            ->ordered()
            ->get()
            ->groupBy('department_id');

        // Grouping keeps the display order of Survey::orderedQuestions().
        return $questions
            ->groupBy(fn (SurveyQuestion $question): int => $question->department_id ?? 0)
            ->map(function (Collection $stepQuestions, int $departmentId) use ($sectionsByDepartment): array {
                $sections = $sectionsByDepartment->get($departmentId, new Collection);
                $department = $stepQuestions->first()->department?->setRelation('sections', $sections);

                return [
                    'key' => $departmentId === 0 ? 'general' : 'department-'.$departmentId,
                    'department' => $department,
                    'sections' => $sections,
                    'questions' => $stepQuestions
                        ->filter(fn (SurveyQuestion $question): bool => $question->department_section_id === null
                            || $sections->contains('id', $question->department_section_id))
                        ->values(),
                ];
            })
            ->filter(fn (array $step): bool => $step['questions']->isNotEmpty())
            ->values();
    }
}
