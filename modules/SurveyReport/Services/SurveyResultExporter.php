<?php

namespace Modules\SurveyReport\Services;

use App\Models\Organization\Company;
use App\Models\Organization\DepartmentSection;
use App\Models\Survey\SurveyAnswer;
use App\Models\Survey\SurveyResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\SurveyReport\Support\ReportScope;

/**
 * Exports the anonymous responses of a survey as CSV (one row per response).
 *
 * The respondent company is only written for companies that reached the
 * anonymity threshold, and every cell is protected against spreadsheet
 * formula injection.
 */
class SurveyResultExporter
{
    /**
     * Write the CSV to an open stream.
     *
     * @param  resource  $stream
     */
    public function write(ReportScope $scope, $stream): void
    {
        $columns = $this->columns($scope);
        $companyLabels = $this->companyLabels($scope);
        $otherCompanyLabel = __('Other');

        fwrite($stream, "\xEF\xBB\xBF");
        $this->writeRow($stream, [__('No'), __('Company'), ...array_column($columns, 'header')]);

        $number = 0;

        $this->responseQuery($scope)
            ->with('answers')
            ->orderBy('id')
            ->chunk(200, function (Collection $responses) use ($stream, $columns, $companyLabels, $otherCompanyLabel, &$number): void {
                foreach ($responses as $response) {
                    $answers = $response->answers->keyBy(
                        fn (SurveyAnswer $answer): string => $answer->survey_question_id.':'.($answer->department_section_id ?? 0)
                    );
                    $row = [++$number, $companyLabels[(int) ($response->company_id ?? 0)] ?? $otherCompanyLabel];

                    foreach ($columns as $column) {
                        $answer = $answers->get($column['key']);
                        $row[] = $column['is_rating'] ? $answer?->rating : $answer?->comment;
                    }

                    $this->writeRow($stream, $row);
                }
            });
    }

    /**
     * One column per question and evaluated section, in display order, e.g.
     * "Q1 [External Affairs / Asset Protection] Please rate … of Asset Protection …".
     * A question answered per section gets a column for every section with answers.
     * Only the departments of the scope are exported (a manager's own). Headers
     * are in the user's language: a standard question's English text is its
     * translation key, questions written by admins stay as typed.
     *
     * @return array<int, array{key: string, header: string, is_rating: bool}>
     */
    protected function columns(ReportScope $scope): array
    {
        $answeredSections = SurveyAnswer::query()
            ->toBase()
            ->whereIn('survey_response_id', $this->responseQuery($scope)->select('id'))
            ->select('survey_question_id', 'department_section_id')
            ->distinct()
            ->get();

        $sections = DepartmentSection::query()
            ->whereIn('id', $answeredSections->pluck('department_section_id')->filter()->unique())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $columns = [];

        foreach ($scope->survey->orderedQuestions() as $index => $question) {
            if (! $scope->coversDepartment($question->department_id)) {
                continue;
            }

            $sectionIds = $answeredSections->where('survey_question_id', $question->id)->pluck('department_section_id');
            $targets = $sections->whereIn('id', $sectionIds->filter())->values()->all();

            if ($targets === [] || $sectionIds->contains(null)) {
                array_unshift($targets, null);
            }

            foreach ($targets as $section) {
                $columns[] = [
                    'key' => $question->id.':'.($section?->id ?? 0),
                    'header' => __('Q:number [:context] :question', [
                        'number' => $index + 1,
                        'context' => collect([$question->department?->name ?? __('General'), $section?->name])->filter()->implode(' / '),
                        'question' => $question->textFor($section?->name ?? __('[section]')),
                    ]),
                    'is_rating' => $question->isRating(),
                ];
            }
        }

        return $columns;
    }

    /**
     * Responses of the survey inside the scope.
     *
     * @return Builder<SurveyResponse>
     */
    protected function responseQuery(ReportScope $scope): Builder
    {
        return SurveyResponse::query()
            ->where('survey_id', $scope->survey->getKey())
            ->when($scope->isRestricted(), fn (Builder $query) => $query->whereIn('company_id', $scope->companyIds));
    }

    /**
     * Company label per company id (0 = unassigned); groups below the
     * anonymity threshold are reported as "Other".
     *
     * @return array<int, string>
     */
    protected function companyLabels(ReportScope $scope): array
    {
        $minGroupSize = max(1, (int) config('survey.min_group_size', 5));
        $unassignedLabel = __('Unassigned company');
        $otherLabel = __('Other');

        $counts = SurveyResponse::query()
            ->toBase()
            ->where('survey_id', $scope->survey->getKey())
            ->when($scope->isRestricted(), fn ($query) => $query->whereIn('company_id', $scope->companyIds))
            ->selectRaw('company_id, COUNT(*) as total')
            ->groupBy('company_id')
            ->get();

        $names = Company::query()->whereIn('id', $counts->pluck('company_id')->filter())->pluck('name', 'id');
        $labels = [];

        foreach ($counts as $count) {
            $companyId = (int) ($count->company_id ?? 0);

            $labels[$companyId] = (int) $count->total >= $minGroupSize
                ? ($names[$companyId] ?? $unassignedLabel)
                : $otherLabel;
        }

        return $labels;
    }

    /**
     * Write one CSV row (RFC 4180, formula-safe).
     *
     * @param  resource  $stream
     * @param  array<int, mixed>  $row
     */
    protected function writeRow($stream, array $row): void
    {
        fputcsv($stream, array_map($this->safeCell(...), $row), ',', '"', '');
    }

    /**
     * Neutralise values a spreadsheet would interpret as a formula.
     */
    protected function safeCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
