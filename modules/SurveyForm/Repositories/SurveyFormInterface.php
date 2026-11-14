<?php

namespace Modules\SurveyForm\Repositories;

use App\Models\Organization\Department;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\Survey;
use App\Models\Survey\SurveyDraft;
use App\Models\Survey\SurveyParticipation;
use App\Models\Survey\SurveyQuestion;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface SurveyFormInterface
{
    /**
     * Surveys open today, flagged with whether the user already completed
     * them (has_participated) or saved a draft (has_draft).
     *
     * @return Collection<int, Survey>
     */
    public function availableFor(User $user): Collection;

    /**
     * The user's latest completed surveys.
     *
     * @return Collection<int, SurveyParticipation>
     */
    public function participationHistory(User $user, int $limit = 10): Collection;

    /**
     * Whether the user already completed the survey.
     */
    public function hasParticipated(Survey $survey, User $user): bool;

    /**
     * The user's saved draft of the survey, if any.
     */
    public function draftFor(Survey $survey, User $user): ?SurveyDraft;

    /**
     * Create or replace the user's draft (encrypted answers and current step).
     *
     * @param  array{answers: array<int|string, mixed>, evaluations: array<int|string, mixed>, step: int|null}  $payload
     */
    public function saveDraft(Survey $survey, User $user, array $payload): SurveyDraft;

    /**
     * Wizard steps: one per department (display order) followed by the general
     * questions. A step lists the active sections of the department, which
     * are all evaluated, and the questions in display order: a department
     * question is answered for every section, a question tied to a section
     * only for that section (questions of inactive sections are left out).
     * Without sections, the questions are answered once.
     *
     * @return SupportCollection<int, array{key: string, department: Department|null, sections: Collection<int, DepartmentSection>, questions: Collection<int, SurveyQuestion>}>
     */
    public function stepsFor(Survey $survey): SupportCollection;
}
